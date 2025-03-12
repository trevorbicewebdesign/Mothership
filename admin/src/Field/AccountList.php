<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

\defined('_JEXEC') or die;

class AccountListField extends ListField
{
    protected $type = 'AccountList';

    public function getOptions()
    {
        $options = MothershipHelper::getAccountListOptions();
        return array_merge(parent::getOptions(), $options);
    }
}
