<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;

\defined('_JEXEC') or die;

class ClientListField extends ListField
{
    protected $type = 'ClientList';

    public function getOptions()
    {
        $options = \TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper::getClientListOptions();
        return array_merge(parent::getOptions(), $options);
    }
}
