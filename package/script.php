<?php

/**
 * @package     Mothership
 * @subpackage  pkg_mothership
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Installer script for the Mothership package.
 *
 * Joomla installs an administrator module as an unpublished instance with no position,
 * so nothing would show after the install. This puts the dashboard modules on the
 * Mothership dashboard the first time they are installed, and leaves them alone after
 * that: an update never moves, republishes or re-adds a module the site owner changed.
 */
class Pkg_MothershipInstallerScript
{
    /** The module position of index.php?option=com_cpanel&view=cpanel&dashboard=mothership */
    private const DASHBOARD_POSITION = 'cpanel-mothership';

    /** module => width of its card on the dashboard, in twelfths, in display order */
    private const MODULES = [
        'mod_mothership_invoices' => 12,
        'mod_mothership_invoiced' => 6,
        'mod_mothership_payments' => 6,
        'mod_mothership_expenses' => 6,
    ];

    /** @var string[] Dashboard modules that were installed before this run. */
    private array $installedBefore = [];

    public function preflight($type, $adapter): bool
    {
        $db      = Factory::getContainer()->get(DatabaseInterface::class);
        $modules = array_keys(self::MODULES);
        $query   = $db->getQuery(true)
            ->select($db->quoteName('element'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('module'))
            ->where($db->quoteName('client_id') . ' = 1')
            ->whereIn($db->quoteName('element'), $modules, ParameterType::STRING);

        $this->installedBefore = $db->setQuery($query)->loadColumn() ?: [];

        return true;
    }

    public function postflight($type, $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $db       = Factory::getContainer()->get(DatabaseInterface::class);
        $ordering = 0;

        foreach (self::MODULES as $module => $width) {
            $ordering++;

            if (\in_array($module, $this->installedBefore, true)) {
                continue;
            }

            // The instance Joomla created when it installed the module.
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__modules'))
                ->where($db->quoteName('module') . ' = ' . $db->quote($module))
                ->where($db->quoteName('client_id') . ' = 1')
                ->where($db->quoteName('position') . ' = ' . $db->quote(''))
                ->where($db->quoteName('published') . ' = 0');

            foreach ($db->setQuery($query)->loadColumn() ?: [] as $id) {
                $instance = (object) [
                    'id'        => (int) $id,
                    'position'  => self::DASHBOARD_POSITION,
                    'published' => 1,
                    'ordering'  => $ordering,
                    'params'    => json_encode(['bootstrap_size' => (string) $width, 'header_tag' => 'h2']),
                ];
                $db->updateObject('#__modules', $instance, 'id');

                // A module shows only once it is assigned to pages; 0 is all of them.
                $assigned = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__modules_menu'))
                    ->where($db->quoteName('moduleid') . ' = ' . (int) $id);

                if (!(int) $db->setQuery($assigned)->loadResult()) {
                    $assignment = (object) ['moduleid' => (int) $id, 'menuid' => 0];
                    $db->insertObject('#__modules_menu', $assignment);
                }
            }
        }

        // The payment plugins are installed disabled on purpose. Each one shows clients
        // payment details (a payee, a Zelle address) that are empty until someone enters them.
        $enabled = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('mothership-payment'))
            ->where($db->quoteName('enabled') . ' = 1');

        if ($type === 'install' && !(int) $db->setQuery($enabled)->loadResult()) {
            Factory::getApplication()->enqueueMessage(
                'Mothership is installed. Payment methods are off until you set them up: open'
                . ' System > Plugins, filter by the type "mothership-payment", enter your details and enable'
                . ' the ones you accept.',
                'notice'
            );
        }

        return true;
    }
}
