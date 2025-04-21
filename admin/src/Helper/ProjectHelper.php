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
    public static function scanWebsiteProject(string $url): array
    {
        $url = rtrim($url, '/');
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? null;
        $path = $parsedUrl['path'] ?? null;

        $headers = [];
        // scan the website for headers
        if ($host) {
            $headers = get_headers($url, 1);
        }

        if ($host === null) {
            return ['error' => 'Invalid URL provided.'];
        }

        return [
            'status' => 'success',
            'message' => 'Scan completed successfully.',
            'data' => [
                'host' => $host,
                'path' => $path,
                'headers' => $headers,

            ]
        ];
    }

    public static function detectJoomla(array $headers, string $html): array
    {
        $joomlaHeaders = [];

        foreach ($headers as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (stripos($v, 'Joomla') !== false) {
                        $joomlaHeaders[$key] = $v;
                        return true;
                    }
                }
            } else {
                if (stripos($value, 'Joomla') !== false) {
                    $joomlaHeaders[$key] = $value;
                    return true;
                }
            }
        }

        return false;
    }
}
