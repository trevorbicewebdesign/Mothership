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
