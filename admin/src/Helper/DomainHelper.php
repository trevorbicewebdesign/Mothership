<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Iodev\Whois\Factory as WhoisFactory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Mothership Domain component helper.
 *
 * @since  1.6
 */
class DomainHelper extends ContentHelper
{

    /**
     * Scans the WHOIS information of a given domain name and returns the details.
     *
     * @param string $domainName The domain name to scan.
     *
     * @return array An associative array containing the following keys:
     *               - 'available' (bool): Indicates if the domain is available.
     *               - 'message' (string): A message about the domain's availability or error details.
     *               - 'domain' (string|null): The domain name (if available in WHOIS data).
     *               - 'registrar' (string|null): The registrar of the domain (if available in WHOIS data).
     *               - 'creationDate' (\DateTime|null): The creation date of the domain (if available in WHOIS data).
     *               - 'expirationDate' (\DateTime|null): The expiration date of the domain (if available in WHOIS data).
     *               - 'whoisServer' (string|null): The WHOIS server used (if available in WHOIS data).
     *               - 'status' (array|null): The status of the domain (if available in WHOIS data).
     *               - 'rawText' (string|null): The raw WHOIS response text (if available in WHOIS data).
     *               - 'error' (bool|null): Indicates if an error occurred during the scan.
     *               - 'message' (string): A message describing the error, if applicable.
     *
     * @throws \Exception If an unexpected error occurs during the WHOIS scan.
     *
     * @example
     * $result = DomainHelper::scanDomain('example.com');
     * if (isset($result['error']) && $result['error']) {
     *     echo 'Error: ' . $result['message'];
     * } elseif ($result['available']) {
     *     echo 'Domain is available: ' . $result['message'];
     * } else {
     *     echo 'Domain is not available. Details:' . PHP_EOL;
     *     echo 'Domain: ' . $result['domain'] . PHP_EOL;
     *     echo 'Registrar: ' . $result['registrar'] . PHP_EOL;
     *     echo 'Creation Date: ' . $result['creationDate']->format('Y-m-d') . PHP_EOL;
     *     echo 'Expiration Date: ' . $result['expirationDate']->format('Y-m-d') . PHP_EOL;
     *     echo 'WHOIS Server: ' . $result['whoisServer'] . PHP_EOL;
     *     echo 'Status: ' . implode(', ', $result['status']) . PHP_EOL;
     * }
     */
    public static function scanDomain(string $domainName): array
    {
        $whois = WhoisFactory::get()->createWhois();

        try {
            $info = $whois->loadDomainInfo($domainName);

            $domain_name = $info->getDomainName() ?: null;
            $creation_date = $info->getCreationDate() ?: null;
            $expiration_date = $info->getExpirationDate() ?: null;
            $registrar = $info->getRegistrar() ?: null;
            $name_servers = $info->getNameServers() ?: null;
            $states = $info->getStates() ?: null;

            // Extracting the extra information from the domain info
            $data = $info->getData() ?: null;
            $extra = $info->getExtra() ?: null;

            if( isset($extra['groups'][0]["Reseller"]) ) {
                $reseller = $extra['groups'][0]["Reseller"];
            } else {
                $reseller = "";
            }
           
            if( isset($extra['groups'][0]["Domain Status"]) ) {
                $domain_status = $extra['groups'][0]["Domain Status"];
                foreach ($domain_status as $key => $value) {
                    $domain_status[$key] = preg_replace('/\s*\(.*\)$/', '', $value);
                }
            } else {
                $domain_status = null;
            }

            $updated_date = $data['updatedDate'] ?: null;

            // Check the domain of the name servers
            if( is_array($name_servers) ) {                    
                foreach ($name_servers as $key => $value) {
                    // strip eric.ns.cloudflare.com down to just cloudflare 
                    $dns_provider = preg_replace('/^(?:[^.]+\.)?([^\.]+)\.[^.]+$/', '$1', $value);
                }
            }
            else {
                $dns_provider = null;
            }
            
            if (!$info) {
                return [
                    'available' => true,
                    'message' => 'Domain appears to be available',
                ];
            }

            return [
                'domain' => $domain_name,
                'creation_date' => $creation_date,
                'expiration_date' => $expiration_date,
                'updated_date' => $updated_date,
                'registrar' => $registrar,
                'reseller' => $reseller,
                'epp_status' => $domain_status,
                'name_servers' => $name_servers,
                'dns_provider' => $dns_provider,
                'data' => $data,
                'extra' => $extra,
                'rawText' => json_encode($info)
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'WHOIS scan failed: ' . $e->getMessage(),
            ];
        }
    }


   
    /**
     * Retrieves a domain record from the database based on the provided domain ID.
     *
     * @param int|string $domain_id The ID of the domain to retrieve.
     * 
     * @return object|null The domain object if found, or null if no matching record exists.
     *
     * @throws \RuntimeException If there is an error executing the database query.
     */
    public static function getDomain(int $domain_id)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName([
                '*'
            ]))
            ->from($db->quoteName('#__mothership_domains'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($domain_id));

        $db->setQuery($query);
        $Domain = $db->loadObject();

        return $Domain;
    }

    /**
     * Get the status of a domain as a string based on its status ID.
     *
     * This method transforms a domain status ID (integer) into a corresponding
     * human-readable string representation.
     *
     * @param int $status_id The status ID of the domain.
     *                       - 1: Active
     *                       - 2: Inactive
     *                       - 3: Pending
     *                       - 4: Suspended
     *                       - Any other value: Unknown
     *
     * @return string The string representation of the domain status.
     */
    public static function getStatus(int $status_id)
    {
        // Transform the domain status from integer to string
        switch ($status_id) {
            case 1:
                $status = 'Active';
                break;
            case 2:
                $status = 'Inactive';
                break;
            case 3:
                $status = 'Pending';
                break;
            case 4:
                $status = 'Suspended';
                break;
            default:
                $status = 'Unknown';
                break;
        }

        return $status;
    }
}
