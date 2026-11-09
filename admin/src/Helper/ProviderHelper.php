<?php
/**
 * Payment Helper for Mothership Payment Plugins
 *
 * Provides methods to update an invoice record, insert payment data, 
 * and allocate the payment to the corresponding invoice.
 *
 * @package     Mothership
 * @subpackage  Helper
 * @copyright   (C) 2025 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Symfony\Component\Yaml\Yaml;
use Joomla\CMS\Factory;

class ProviderHelper
{
    public static function loadProviders()
    {
        $path = JPATH_ADMINISTRATOR . '/components/com_mothership/config/domain_services.yaml';

        if (!is_file($path)) {
            return [];
        }

        try {
            return Yaml::parseFile($path);
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error parsing YAML: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    public static function getOptionsByType($type)
    {
        $all = self::loadProviders();
        return $all[$type] ?? [];
    }
}
