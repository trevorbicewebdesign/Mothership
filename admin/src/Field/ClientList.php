<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

\defined('_JEXEC') or die;

class ClientListField extends ListField
{
    protected $type = 'ClientList';

    public function getOptions()
    {
        $options = MothershipHelper::getClientListOptions();
        return array_merge(parent::getOptions(), $options);
    }
}
