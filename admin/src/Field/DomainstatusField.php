<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;


\defined('_JEXEC') or die;

class DomainstatusField extends ListField
{
    protected $type = 'Domainstatus';

    public function getOptions()
    {
        $options = [
            HTMLHelper::_('select.option', 'active', 'Active'),
            HTMLHelper::_('select.option', 'expired', 'Expired'),
            HTMLHelper::_('select.option', 'transferring', 'Transferring'),
        ];

        array_unshift($options, HTMLHelper::_('select.option', '', Text::_('COM_MOTHERSHIP_SELECT_DOMAIN_STATUS')));
        return array_merge(parent::getOptions(), is_array($options) ? $options : []);
    }
}

