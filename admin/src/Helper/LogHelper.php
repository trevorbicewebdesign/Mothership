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
 * Mothership Log component helper.
 *
 * @since  1.6
 */
class LogHelper extends ContentHelper
{
    public static function log(array $params): bool
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__mothership_logs'))
            ->columns([
                'client_id', 'account_id', 'object_type', 'object_id', 'action',
                'meta', 'description', 'details', 'user_id', 'ip_address', 'created'
            ])
            ->values(implode(',', [
                $db->quote($params['client_id'] ?? null),
                $db->quote($params['account_id'] ?? null),
                $db->quote($params['object_type'] ?? null),
                $db->quote($params['object_id'] ?? null),
                $db->quote($params['action'] ?? null),
                $db->quote(json_encode($params['meta'] ?? [])),
                $db->quote($params['description'] ?? null),
                $db->quote($params['details'] ?? null),
                $db->quote($params['user_id'] ?? Factory::getUser()->id),
                $db->quote($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
                $db->quote(date('Y-m-d H:i:s')),
            ]));

        $db->setQuery($query);
        return $db->execute();
    }
}
