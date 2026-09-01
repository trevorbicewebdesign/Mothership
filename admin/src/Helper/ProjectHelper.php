<?php
/**
 * Payment Helper for Mothership Payment Plugins
 *
 * Provides methods to update an invoice record, insert payment data, 
 * and allocate the payment to the corresponding invoice.
 *
 * @package     Mothership
 * @subpackage  Helper
 * @copyright   (C) 2025 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;

class ProjectHelper
{

    public static function getProjectListOptions($account_id=NULL)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name']))
            ->from($db->quoteName('#__mothership_projects'));

        if ($account_id !== null) {
            $query->where($db->quoteName('account_id') . ' = ' . $db->quote($account_id));
        }

        $query->order($db->quoteName('name') . ' ASC');

        $db->setQuery($query);
        $accounts = $db->loadObjectList();

        $options = [];

        // Add placeholder option
        $options[] = HTMLHelper::_('select.option', '', Text::_('COM_MOTHERSHIP_SELECT_PROJECT'));

        // Build options array
        if ($accounts) {
            foreach ($accounts as $account) {
                $options[] = HTMLHelper::_('select.option', $account->id, $account->name);
            }
        }

        return $options;
    }

    /**
     * Scans a website URL and retrieves headers, HTML, cookies, and other data
     * that can be used to identify the platform.
     *
     * @param string $url The URL of the website to scan (https://mothership.trevorbice.com).
     * @return array An array containing the scan results or an error message.
     */
    public static function scanWebsiteProject(string $url): array
    {
        $url = rtrim($url, '/');
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['error' => 'Invalid URL provided.'];
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? null;
        $path = $parsedUrl['path'] ?? null;


        if ($host === null) {
            return ['error' => 'Invalid URL provided.'];
        }

        $headers = [];
        $html = '';
        $cookies = [];

        // A real browser UA so security layers (RS Firewall, Cloudflare, WAFs)
        // and picky servers don't 403 the health check.
        $userAgent = 'Mozilla/5.0 (compatible; MothershipScanner/1.0; +https://webdesign.trevorbice.com)';

        // Optional shared secret so a site's WAF can *allowlist* this scanner:
        // set com_mothership's "scan_secret", then add a WAF rule that allows
        // requests carrying the X-Mothership-Scan header. When allowlisted the
        // scanner gets real content (status + keyword + TTFB) instead of a 403.
        $scanSecret   = (string) \Joomla\CMS\Component\ComponentHelper::getParams('com_mothership')->get('scan_secret', '');
        $extraHeaders = $scanSecret !== '' ? ['X-Mothership-Scan: ' . $scanSecret] : [];

        // Authoritative reachability + final status via cURL: follows redirects
        // (http->https, www), sends a UA, and returns an INTEGER code — HTTP/2
        // status lines have no "200 OK" reason phrase, so string matching fails.
        $httpCode  = 0;
        $curlError = '';
        $finalUrl  = $url;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => false, // health check: don't fail on cert quirks
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => $extraHeaders,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $curlError = curl_error($ch);
            Log::add('Scan cURL error for ' . $url . ': ' . $curlError, Log::WARNING, 'scanWebsiteProject');
        } else {
            $html = (string) $body;
        }
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        // Response headers (for CMS detection) — UA-aware + follows redirects.
        try {
            $context = stream_context_create([
                'http' => ['user_agent' => $userAgent, 'follow_location' => 1, 'timeout' => 15, 'ignore_errors' => true, 'header' => implode("\r\n", $extraHeaders)],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $headers = @get_headers($url, 1, $context) ?: [];
        } catch (\Exception $e) {
            Log::add('Failed to retrieve headers: ' . $e->getMessage(), Log::WARNING, 'scanWebsiteProject');
        }

        return [
            'status' => 'success',
            'message' => 'Scan completed successfully.',
            'data' => [
                'http_code'     => $httpCode,           // reliable integer status
                'response_code' => $headers[0] ?? null, // kept for backward compatibility
                'curl_error'    => $curlError,
                'final_url'     => $finalUrl,
                'host'          => $host,
                'path'          => $path,
                'headers'       => $headers,
                'html'          => $html,
                'cookies'       => $cookies,
            ]
        ];
    }

    public static function getGeneratorMeta($html)
    {
        // First lets parse the html inside the <head> tag into an array
        preg_match_all('/<head.*?>(.*?)<\/head>/si', $html, $matches);
        $headContent = implode(' ', $matches[1]);
        // Get all the html elements inside the head into an array
        preg_match_all('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/si', $headContent, $matches);
        $generator = isset($matches[1][0]) ? trim($matches[1][0]) : null;

        return $generator;
    }

    public static function detectJoomla(array $headers, string $html): bool
    {
        if(preg_match('/<script[^>]+class=[\"|\']joomla-script-options[^\"|\']+[\"|\'].*?>.*?<\/script>/si', $html)) {
            return true;
        }

        return false;
    }

    public static function detectWordpress(array $headers, string $html): bool
    {
        $generator = self::getGeneratorMeta($html);
        if(preg_match('/WordPress/', $generator) || preg_match('/wordpress/', $generator)) {
            return true;
        }
        
        return false;
    }
}
