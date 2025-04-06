<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class ProjectModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('project.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load the project with status and related invoices
        $query = $db->getQuery(true)
            ->select([
                'p.*'
            ])
            ->from($db->quoteName('#__mothership_projects', 'p'))
            ->where('p.id = :id')
            ->where('p.status != -1')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $project = $db->loadObject();

        return $project;
    }


    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('project.id', $id);
    }

}
