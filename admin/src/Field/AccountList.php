<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;

\defined('_JEXEC') or die;

class AccountListField extends ListField
{
    protected $type = 'AccountList';

    public function getOptions()
    {
        $options = \TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper::getAccountListOptions();
        return array_merge(parent::getOptions(), $options);
    }
}
