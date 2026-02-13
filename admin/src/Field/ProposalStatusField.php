<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class ProposalStatusField extends ListField
{
    protected $type = 'proposalstatus';

    protected function getOptions()
    {
        $options = [];

        $statuses = [
            'draft'=>1, 
            'pending'=>2,
            'approved'=>3,
            'declined'=>4,
            'cancelled'=>5,
            'expired'=>6
        ];
        foreach ($statuses as $key=>$status) {
            $options[] = (object) [
            'value' => $status,
            'text'  => Text::_('COM_MOTHERSHIP_PROPOSAL_STATUS_' . strtoupper($key))
            ];
        }
        return array_merge(parent::getOptions(), $options);
    }
}
