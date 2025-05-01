<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;

\defined('_JEXEC') or die;

class clientlistfield extends ListField
{
    protected $type = 'clientlist';

    public function getOptions()
    {
        $options = ClientHelper::getClientListOptions();
        return array_merge(parent::getOptions(), $options);
    }
}
