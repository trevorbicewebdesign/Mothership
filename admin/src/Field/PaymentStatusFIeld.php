<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Enum\Paymentstatus;

defined('_JEXEC') or die;

class PaymentstatusField extends ListField
{
    protected $type = 'Paymentstatus';

    protected function getOptions()
    {
        $options = [];

        $statuses = ['draft'=>1, 'opened'=>2, 'late'=>3, 'paid'=>4];
        foreach ($statuses as $key=>$status) {
            $options[] = (object) [
            'value' => $status,
            'text'  => Text::_('COM_MOTHERSHIP_PAYMENT_STATUS_' . strtoupper($key))
            ];
        }
        return array_merge(parent::getOptions(), $options);
    }
}
