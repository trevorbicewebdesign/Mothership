<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Service\Html;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Banner HTML class.
 *
 * @since  2.5
 */
class Mothership
{
    use DatabaseAwareTrait;

    /**
     * Display a batch widget for the client selector.
     *
     * @return  string  The necessary HTML for the widget.
     *
     * @since   2.5
     */
    public function clients()
    {
        // Create the batch selector to change the client on a selection list.
        return implode(
            "\n",
            [
                '<label id="batch-client-lbl" for="batch-client-id">',
                Text::_('COM_MOTHERSHIP_BATCH_CLIENT_LABEL'),
                '</label>',
                '<select class="form-select" name="batch[client_id]" id="batch-client-id">',
                '<option value="">' . Text::_('COM_MOTHERSHIP_BATCH_CLIENT_NOCHANGE') . '</option>',
                '<option value="0">' . Text::_('COM_MOTHERSHIP_NO_CLIENT') . '</option>',
                HTMLHelper::_('select.options', static::clientlist(), 'value', 'text'),
                '</select>',
            ]
        );
    }
}
