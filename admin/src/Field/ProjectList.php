<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
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

        // Project is optional — offer an explicit "no project" choice so an
        // invoice can be billed to the account alone.
        array_unshift(
            $options,
            HTMLHelper::_('select.option', '', Text::_('COM_MOTHERSHIP_INVOICE_NO_PROJECT'))
        );

        return array_merge(parent::getOptions(), $options);
    }
}
