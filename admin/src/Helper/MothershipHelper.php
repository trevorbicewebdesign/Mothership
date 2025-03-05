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
}
