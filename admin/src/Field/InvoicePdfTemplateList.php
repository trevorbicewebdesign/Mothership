<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;

defined('_JEXEC') or die;

class InvoicePdfTemplateList extends ListField
{
    protected $type = 'invoicepdftemplate';

    public function getOptions()
    {
        $form = $this->form;
        $data = $form->getData();
        $client_id = $data->get('client_id', null);

        $options = AccountHelper::getAccountListOptions($client_id);
        return array_merge(parent::getOptions(), $options);
    }
}
