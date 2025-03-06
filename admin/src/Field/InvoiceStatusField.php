<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Enum\InvoiceStatus;

defined('_JEXEC') or die;

class InvoicestatusField extends ListField
{
    protected $type = 'Invoicestatus';

    protected function getOptions()
    {
        $options = [];

        $statuses = ['draft', 'opened', 'late', 'paid'];
        foreach ($statuses as $status) {
            $options[] = (object) [
            'value' => $status,
            'text'  => Text::_('COM_MOTHERSHIP_INVOICE_STATUS_' . strtoupper($status))
            ];
        }
        return array_merge(parent::getOptions(), $options);
    }
}
