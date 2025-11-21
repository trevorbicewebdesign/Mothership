<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
\defined('_JEXEC') or die;

class InvoicepdftemplateListField extends ListField
{
    protected $type = 'invoicepdftemplatelist';

    public function getOptions()
    {

        \Joomla\CMS\Plugin\PluginHelper::importPlugin('mothership-invoice-pdf');

        $plugins = \Joomla\CMS\Plugin\PluginHelper::getPlugin('mothership-invoice-pdf');

        $form = $this->form;
        $data = $form->getData();

        // The options are the installed plugins, plus `default`
        $options = [];
        $options[] = (object) [
            'value' => 'default',
            'text'  => "Default"
        ];
        foreach ($plugins as $plugin) {
            $options[] = (object) [
                'value' => $plugin->name,
                'text'  => $plugin->name
            ];
        }
        
        return array_merge(parent::getOptions(), $options);
    }
}
