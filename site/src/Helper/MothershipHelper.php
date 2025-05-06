<?php
namespace TrevorBice\Component\Mothership\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Database\DatabaseDriver;
use Joomla\CMS\Language\Text;

class MothershipHelper
{
    /**
     * Get the client ID for the current user.
     *
     * @return int|null The client ID or null if not found.
     */
    public static function getUserClientId($userId = null): ?int
    {
       

        $user = Factory::getUser();

        if ($userId === null) {
            $userId = $user->id;
        }

        if ($user->guest || !$userId) {
            return null;
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__mothership_clients'))
            ->where($db->quoteName('owner_user_id') . ' = ' . (int) $userId);
        $db->setQuery($query);

        return ($result = $db->loadResult()) ? (int) $result : null;
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
