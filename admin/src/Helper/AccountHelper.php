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
 * Mothership Account component helper.
 *
 * @since  1.6
 */
class AccountHelper extends ContentHelper
{
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
    
    public static function getAccount($account_id)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_accounts'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($account_id));

        $db->setQuery($query);
        $account = $db->loadObject();

        return $account;
    }
}
