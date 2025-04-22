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
use Joomla\Database\DatabaseDriver;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;

class ProjectHelper
{
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

        // Attempt to retrieve headers
        try {
            $contextOptions = [
                "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
                ],
            ];
            $context = stream_context_create($contextOptions);
    
            // Attempt to retrieve headers with disabled SSL checks
            $headers = get_headers($url, 1, $context);
        } catch (\Exception $e) {
            Log::add('Failed to retrieve headers: ' . $e->getMessage(), Log::ERROR, 'scanWebsiteProject');
        }

        // Attempt to retrieve HTML content
        try {
            $contextOptions = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
            ];
            $context = stream_context_create($contextOptions);
            $html = file_get_contents($url, false, $context);
        } catch (\Exception $e) {
            Log::add('Failed to retrieve HTML content: ' . $e->getMessage(), Log::ERROR, 'scanWebsiteProject');
        }

        // Attempt to retrieve cookies using cURL
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_COOKIEFILE, '');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL certificate verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Ignore SSL host verification
            curl_exec($ch);
            $cookies = curl_getinfo($ch, CURLINFO_COOKIELIST);
            curl_close($ch);
        } catch (\Exception $e) {
            Log::add('Failed to retrieve cookies: ' . $e->getMessage(), Log::ERROR, 'scanWebsiteProject');
        }

        return [
            'status' => 'success',
            'message' => 'Scan completed successfully.',
            'data' => [
                'host' => $host,
                'path' => $path,
                'headers' => $headers,
                'html' => $html,
                'cookies' => $cookies,
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
