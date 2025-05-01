<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

// Ensure Joomla framework is loaded
if (!class_exists('Joomla\CMS\Form\Field\ListField')) {
    throw new \RuntimeException('Joomla framework not loaded. Ensure the CMS is properly initialized.');
}

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
