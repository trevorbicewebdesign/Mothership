<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;

\defined('_JEXEC') or die;

class accountlistfield extends ListField
{
    protected $type = 'accountlist';

    public function getOptions()
    {
        $options = AccountHelper::getAccountListOptions();
        return array_merge(parent::getOptions(), $options);
    }
}
