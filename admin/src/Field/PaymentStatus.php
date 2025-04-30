<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class PaymentStatusField extends ListField
{
    protected $type = 'PaymentStatus';

    protected function getOptions()
    {
        $options = [];

        $statuses = [
            'pending'=>1, 
            'completed'=>2, 
            'failed'=>3, 
            'canceled'=>4, 
            'refunded'=>5
        ];
        foreach ($statuses as $key=>$status) {
            $options[] = (object) [
            'value' => $status,
            'text'  => Text::_('COM_MOTHERSHIP_PAYMENT_STATUS_' . strtoupper($key))
            ];
        }
        return array_merge(parent::getOptions(), $options);
    }
}
