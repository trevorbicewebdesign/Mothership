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
        $options = ProjectHelper::getProjectListOptions();
        return array_merge(parent::getOptions(), $options);
    }
}
