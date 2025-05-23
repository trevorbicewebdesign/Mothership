<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ProjectHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;

\defined('_JEXEC') or die;


/**
 * Project Controller for com_mothership
 */
class ProjectController extends FormController
{
    protected $default_view = 'project';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function mothershipScan()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Project');

        $project = $model->getItem($input->getInt('id'));
        $projectName = $project->name;

        // Check last_scan value and compare with current time
        $lastScan = strtotime($project->last_scan);
        $currentTime = time();
        $timeDifference = $currentTime - $lastScan;
        // If the current scan was less than 1 hour ago, return an error message about the scan being too recent
        if ($timeDifference < 3600) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PROJECT_SCAN_TOO_RECENT', "<strong>{$project->name}</strong>"), 'error');
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=project&layout=edit&id={$project->id}", false));
            return false;
        }

        $project->metadata = json_decode($project->metadata, true);

        $primary_url = $project->metadata['primary_url'] ?? null;

        $parsedUrl = parse_url($primary_url);
        $project->primary_domain = $parsedUrl['host'] ?? '';

        try {
            // Perform the scan (assuming this method exists in your model)
            $scanResults = ProjectHelper::scanWebsiteProject($primary_url);
        } catch (\Exception $e) {
            echo json_encode(['error' => true, 'message' => $e->getMessage()]);
        }

        LogHelper::logProjectScanned($project->id, $project->client_id, $project->account_id);

        if (strpos($scanResults['data']['response_code'], '200 OK') !== false) {
            $project->status = 'active';
            $project->metadata['status'] = 'online';
        } else {
            $project->status = 'inactive';
            $project->metadata['status'] = 'offline';
        }

        if(!empty($scanResults['data']['headers']) && !empty($scanResults['data']['html'])) {
            if (ProjectHelper::detectJoomla($scanResults['data']['headers'], $scanResults['data']['html'])) {
                $project->metadata['cms_type'] = 'joomla';
            } elseif (ProjectHelper::detectWordPress($scanResults['data']['headers'], $scanResults['data']['html'])) {
                $project->metadata['cms_type'] = 'wordpress';
            } else {
                $project->metadata['cms_type'] = 'None';
            }
        }


        if (!$model->save($project)) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PROJECT_SCAN_UPDATE_FAILED', "<strong>{$project->name}</strong>"), 'message');
            $app->enqueueMessage($model->getError(), 'error');
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=project&layout=edit&id={$project->id}", false));
            return false;
        }

        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PROJECT_SCAN_SUCCESS', "<strong>{$project->name}</strong>"), 'message');

        // Redirect back to the project edit page after scanning
        $this->setRedirect(Route::_("index.php?option=com_mothership&view=project&layout=edit&id={$project->id}", false));
    }

    public function save($key = null, $urlVar = null)
    {
        $app   = Factory::getApplication();
        $input = $app->input;
        $data  = $input->get('jform', [], 'array');
        $model = $this->getModel('Project');

        if (!$model->save($data)) {
            // Error occurred, redirect back to form with error messages
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROJECT_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=project&layout=edit', false));
            return false;
        }

        // Success message
        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PROJECT_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        // Determine which task was requested
        $task = $input->getCmd('task');
        // If "Apply" (i.e., project.apply) is clicked, remain on the edit page.
        if ($task === 'apply') {
            $id = !empty($data['id']) ? $data['id'] : $model->getState($model->getName() . '.id');
            $redirectUrl = Route::_("index.php?option=com_mothership&view=project&layout=edit&id={$id}", false);
        } else {
            // If "Save" (i.e., project.save) is clicked, return to the projects list.
            $redirectUrl = Route::_('index.php?option=com_mothership&view=projects', false);
        }

        $this->setRedirect($redirectUrl);
        return true;
    }

    public function cancel($key = null)
    {
        $model = $this->getModel('Project');
        $id    = $this->input->getInt('id');
        $model->cancelEdit($id);

        $defaultRedirect = Route::_('index.php?option=com_mothership&view=projects', false);
        $redirect = MothershipHelper::getReturnRedirect($defaultRedirect);
        $this->setRedirect($redirect);

        return true;
    }

    public function delete()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Project');
        $cid = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_PROJECT_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROJECT_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROJECT_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect(MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=projects', false)));
    }
}