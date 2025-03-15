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
 * Mothership component helper.
 *
 * @since  1.6
 */
class MothershipHelper extends ContentHelper
{
    /**
     * Retrieves a list of client options for a select dropdown.
     *
     * This method queries the database for a list of clients, sorts them by name,
     * and returns an array of options suitable for use in a select dropdown.
     *
     * @return array An array of select options, each option being an associative array
     *               with 'value' and 'text' keys.
     */
    public static function getClientListOptions()
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name']))
            ->from($db->quoteName('#__mothership_clients'))
            ->order($db->quoteName('name') . ' ASC');

        $db->setQuery($query);
        $clients = $db->loadObjectList();

        $options = [];

        // Add placeholder option
        $options[] = HTMLHelper::_('select.option', '', Text::_('COM_MOTHERSHIP_SELECT_CLIENT'));

        // Build options array
        if ($clients) {
            foreach ($clients as $client) {
                $options[] = HTMLHelper::_('select.option', $client->id, $client->name);
            }
        }

        return $options;
    }

    public static function getAccountListOptions($client_id=NULL)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name']))
            ->from($db->quoteName('#__mothership_accounts'));

        if ($client_id !== null) {
            $query->where($db->quoteName('client_id') . ' = ' . $db->quote($client_id));
        }

        $query->order($db->quoteName('name') . ' ASC');

        $db->setQuery($query);
        $accounts = $db->loadObjectList();

        $options = [];

        // Add placeholder option
        $options[] = HTMLHelper::_('select.option', '', Text::_('COM_MOTHERSHIP_SELECT_ACCOUNT'));

        // Build options array
        if ($accounts) {
            foreach ($accounts as $account) {
                $options[] = HTMLHelper::_('select.option', $account->id, $account->name);
            }
        }

        return $options;
    }

    public function getClient($client_id)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName([
                'id', 
                'name'
            ]))
            ->from($db->quoteName('#__mothership_clients'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($client_id));

        $db->setQuery($query);
        $client = $db->loadObject();

        return $client;
    }

    public static function getInvoiceStatus($status_id)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName('status'))
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($status_id));

        $db->setQuery($query);
        $status = $db->loadResult();

        // Transform the status from integer to string
        switch ($status) {
            case 0:
                $status = 'Draft';
                break;
            case 1:
                $status = 'Opened';
                break;
            case 2:
                $status = 'Late';
                break;
            default:
                $status = 'Paid';
                break;
        }

        return $status;
    }

    /**
     * Get the return URL from the request or form.
     */
    public static function getReturnRedirect($default = null)
    {
        $input = Factory::getApplication()->input;

        // Check URL param
        $return = $input->getBase64('return');

        // Check form data if not found in URL
        if (!$return) {
            $data = $input->get('jform', [], 'array');
            if (!empty($data['return'])) {
                $return = base64_decode($data['return'], true);
                if ($return !== false) {
                    $return = htmlspecialchars_decode($return);
                }
            }
        }

        if (!empty($return)) {
            return $return;
        }

        return $default;
    }
    
}
