<?php
namespace TrevorBice\Component\Mothership\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Database\DatabaseDriver;
use Joomla\CMS\Language\Text;

class MothershipHelper
{
    /**
     * Get the client IDs for the current user.
     *
     * @param  int|null  $userId  The user ID (optional; defaults to the current user).
     * @return array|null  An array of client IDs the user owns, or null if none found.
     */
    public static function getUserClientIds($userId = null): ?array
    {
        // Determine user
        if ($userId === null) {
            $user = Factory::getUser();
            $userId = (int) $user->id;
        } else {
            $user = Factory::getApplication()->getIdentity($userId);
        }

        // Bail out if not logged in or invalid
        if ($user->guest || !$userId) {
            return null;
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__mothership_clients'))
            ->where($db->quoteName('owner_user_id') . ' = ' . (int) $userId);

        $db->setQuery($query);
        $results = $db->loadColumn(); // returns an array of all ids

        return !empty($results) ? array_map('intval', $results) : null;
    }
        
    public static function getClient($clientId): ?object
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__mothership_clients')
            ->where('id = ' . (int) $clientId);
        $db->setQuery($query);

        return $db->loadObject();
    }
}
