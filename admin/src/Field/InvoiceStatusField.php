<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Enum\InvoiceStatus;

defined('_JEXEC') or die;

class invoicestatusfield extends ListField
{
    protected $type = 'invoicestatus';

    protected function getOptions()
    {
        $options = [];

        $statuses = [
            'draft'=>1, 
            'opened'=>2, 
            'canceled'=>3, 
            'closed'=>4
        ];
        foreach ($statuses as $key=>$status) {
            $options[] = (object) [
            'value' => $status,
            'text'  => Text::_('COM_MOTHERSHIP_INVOICE_STATUS_' . strtoupper($key))
            ];
        }
        return array_merge(parent::getOptions(), $options);
    }
}
