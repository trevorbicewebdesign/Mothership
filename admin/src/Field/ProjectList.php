<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use TrevorBice\Component\Mothership\Administrator\Helper\ProjectHelper;

\defined('_JEXEC') or die;

class ProjectListField extends ListField
{
    protected $type = 'projectlist';

    public function getOptions()
    {
        $form = $this->form;
        $data = $form->getData();
        $account_id = $data->get('account_id', null);

        $options = ProjectHelper::getProjectListOptions($account_id);
        return array_merge(parent::getOptions(), $options);
    }
}
