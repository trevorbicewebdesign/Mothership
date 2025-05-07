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
     * Get the return URL from the request or form.
     */
    public static function getReturnRedirect($default = null)
    {
        $input = Factory::getApplication()->input;

        // Check URL param
        $return = $input->getString('return', '');
        $return = base64_decode($return, true);

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

    public static function getMothershipOptions($option_name = null)
    {
        $params = ComponentHelper::getParams('com_mothership');
        $options = [];
            
        $options['company_name'] = $params->get('company_name', '');
        $options['company_email'] = $params->get('company_email', '');
        $options['company_address_1'] = $params->get('company_address_1', '');
        $options['company_address_2'] = $params->get('company_address_2', '');
        $options['company_city'] = $params->get('company_city', '');
        $options['company_state'] = $params->get('company_state', '');
        $options['company_zip'] = $params->get('company_zip', '');
        $options['company_phone'] = $params->get('company_phone', '');
        $options['company_default_rate'] = $params->get('company_default_rate', '');

        if($option_name == null) {
            return $options;
        }
        if (array_key_exists($option_name, $options)) {
            return $options[$option_name];
        }

        return $options;
    }
    
}
