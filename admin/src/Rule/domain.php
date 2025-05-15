<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2025 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Rule;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\Registry\Registry;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class DomainRule extends FormRule
{

    public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
	{
        die("JFormRuleDomain Loaded");
		// If the field is empty and not required, the field is valid.
		$required = ((string) $element['required'] === 'true' || (string) $element['required'] === 'required');

		$minimumLength = 6;

		if (!$required && empty($value))
		{
			return true;
		}

		$valueLength = strlen($value);

		// We don't allow white space inside passwords
		$valueTrim = trim($value);

		// Set a variable to check if any errors are made in password
		$validPassword = true;

		if (strlen($valueTrim) !== $valueLength)
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_RSFIREWALL_MSG_SPACES_IN_PASSWORD'),
				'warning'
			);

			$validPassword = false;
		}

		if (strlen((string) $value) < $minimumLength)
		{
			Factory::getApplication()->enqueueMessage(
				Text::plural('COM_RSFIREWALL_MSG_PASSWORD_TOO_SHORT_N', $minimumLength),
				'warning'
			);

			$validPassword = false;
		}

		return $validPassword;
	}
}
