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

        // Load the project with status and related client/account names
        $query = $db->getQuery(true)
            ->select([
                'p.*',
                'c.name AS client_name',
                'a.name AS account_name'
            ])
            ->from($db->quoteName('#__mothership_projects', 'p'))
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON p.client_id = c.id')
            ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON p.account_id = a.id')
            ->where('p.id = :id')
            ->where('p.status != -1')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $project = $db->loadObject();

        // ✅ Decode metadata if present
        if ($project && isset($project->metadata) && is_string($project->metadata)) {
            $decoded = json_decode($project->metadata, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $project->metadata = $decoded;
            } else {
                $project->metadata = null;
            }
        }

        return $project;
    }


    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('project.id', $id);
    }

}
