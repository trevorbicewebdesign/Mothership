<?php

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class com_mothershipInstallerScript
{
    public function postflight($type, $parent)
    {
        if (!in_array($type, ['install', 'update'], true)) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Find the main "Mothership" admin menu item (parent)
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'menutype']))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_mothership'))
            ->where($db->quoteName('client_id') . ' = 1'); // admin
        $db->setQuery($query);
        $parent = $db->loadObject();

        if (!$parent) {
            // Component menu not found; bail instead of guessing
            return;
        }

        // Check if Proposals submenu already exists
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_mothership&view=proposals'))
            ->where($db->quoteName('client_id') . ' = 1');
        $db->setQuery($query);
        $id = (int) $db->loadResult();

        if ($id) {
            return; // already there, don’t duplicate
        }

        // Insert new submenu item under the main Mothership admin menu
        $columns = [
            'menutype', 'title', 'alias', 'path', 'link', 'type', 'published',
            'parent_id', 'component_id', 'access', 'client_id', 'language'
        ];

        $values = [
            $db->quote($parent->menutype),
            $db->quote('COM_MOTHERSHIP_SUBMENU_PROPOSALS'),
            $db->quote('proposals'),
            $db->quote('mothership/proposals'),
            $db->quote('index.php?option=com_mothership&view=proposals'),
            $db->quote('component'),
            1,                    // published
            (int) $parent->id,
            (int) $this->getComponentId($db),
            1,                    // Public access
            1,                    // admin client
            $db->quote('*')
        ];

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__menu'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        $db->setQuery($query);
        $db->execute();
    }

    private function getComponentId(DatabaseInterface $db): int
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_mothership'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        return (int) $db->loadResult();
    }
}
