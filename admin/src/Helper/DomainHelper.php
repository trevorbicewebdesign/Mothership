<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

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
