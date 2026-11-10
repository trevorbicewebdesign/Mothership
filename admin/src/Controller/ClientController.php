<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

\defined('_JEXEC') or die;

/**
 * Client Controller for com_mothership
 */
class ClientController extends FormController
{
    protected $default_view = 'client';


    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function save($key = null, $urlVar = null)
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();
        $data  = $input->get('jform', [], 'array');
        $model = $this->getModel('Client');
        $task  = $input->getCmd('task');

        if (!$model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');

            if ($task === 'apply') {
                $id = (int) ($model->getState($model->getName() . '.id') ?: ($data['id'] ?? 0));
                $this->setRedirect(Route::_('index.php?option=com_mothership&view=client&layout=edit&id=' . $id, false));
                return false;
            }

            $this->setRedirect(Route::_('index.php?option=com_mothership&view=clients', false));
            return false;
        }
        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_CLIENT_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        // Always fetch the freshly saved ID from the model state
        $id = (int) ($model->getState($model->getName() . '.id') ?: ($data['id'] ?? 0));

        if ($task === 'apply') {
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=client&layout=edit&id=' . $id, false));
        } else {
            $model->cancelEdit((int) $data['id']);
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=clients', false));
        }

        return true;
    }

    public function cancel($key = null)
    {
        $model = $this->getModel('Client');
        $id = $this->input->getInt('id');
        $model->cancelEdit($id);

        $defaultRedirect = Route::_('index.php?option=com_mothership&view=clients', false);
        $returnRedirect = MothershipHelper::getReturnRedirect($defaultRedirect);

        $this->setRedirect($returnRedirect);

        return true;
    }
    
    public function delete()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $model = $this->getModel('Client');
        $cid = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_CLIENT_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect(MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=clients', false)));
    }

    /** Users tab: give someone portal access, creating and emailing a login when needed. */
    public function inviteUser()
    {
        $clientId = $this->accessClientId();
        $input    = $this->input;
        $email    = trim($input->getString('email', ''));

        try {
            // Staff usually invite for the client, so the email can come from the owner.
            $ownerId    = (int) (ClientHelper::getClient($clientId)->owner_user_id ?? 0);
            $onBehalfOf = ($input->getInt('on_behalf_of_owner') === 1 && $ownerId) ? $ownerId : null;

            $result = ClientAccess::invite($clientId, $input->getString('name', ''), $email, null, $onBehalfOf);
            $shown  = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

            if (!$result['new_login']) {
                $this->app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_CLIENT_USERS_ADDED_EXISTING', $shown), 'message');
            } elseif ($result['emailed']) {
                $this->app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_CLIENT_USERS_INVITED', $shown), 'message');
            } else {
                $this->app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_CLIENT_USERS_INVITED_NO_EMAIL', $shown), 'warning');
            }
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->accessRedirect($clientId);
    }

    /** Users tab: take someone's access to this client away. */
    public function removeUser()
    {
        $clientId = $this->accessClientId();

        try {
            ClientAccess::remove($this->accessRowId($clientId));
            $this->app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_USERS_REMOVED'), 'message');
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->accessRedirect($clientId);
    }

    /** Users tab: send a fresh link to someone who hasn't set their password yet. */
    public function resendInvite()
    {
        $clientId = $this->accessClientId();

        try {
            $sent = ClientAccess::resendInvite($this->accessRowId($clientId));
            $this->app->enqueueMessage(
                Text::_($sent['emailed'] ? 'COM_MOTHERSHIP_CLIENT_USERS_RESENT' : 'COM_MOTHERSHIP_CLIENT_USERS_RESEND_FAILED'),
                $sent['emailed'] ? 'message' : 'warning'
            );
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->accessRedirect($clientId);
    }

    /** Token and edit permission for the Users tab actions; returns the client id. */
    private function accessClientId(): int
    {
        $this->checkToken();

        if (!$this->app->getIdentity()->authorise('core.edit', 'com_mothership')) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
        }

        $clientId = $this->input->getInt('id');

        if (!$clientId) {
            throw new \Exception(Text::_('COM_MOTHERSHIP_ERROR_CLIENT_NOT_FOUND'), 404);
        }

        return $clientId;
    }

    /** The posted access row id, when it belongs to this client. */
    private function accessRowId(int $clientId): int
    {
        $row = ClientAccess::accessRow($this->input->getInt('access_id'));

        if ((int) $row->client_id !== $clientId) {
            throw new \RuntimeException(Text::_('COM_MOTHERSHIP_CLIENT_USERS_NOT_THIS_CLIENT'));
        }

        return (int) $row->id;
    }

    private function accessRedirect(int $clientId): void
    {
        $this->setRedirect(Route::_('index.php?option=com_mothership&view=client&layout=edit&id=' . $clientId, false));
    }

    public function getDefaultRate()
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');
        
        $defaultCompanyRate = MothershipHelper::getMothershipOptions('company_default_rate');
        
        try 
        {
            $client = ClientHelper::getClient($id);
            $defaultRate = isset($client->default_rate) && $client->default_rate !== '' ? $client->default_rate : $defaultCompanyRate;
        } catch (\Exception $e) {
            echo json_encode([
                'error' => $e->getMessage(),
            ]);
            $app->setHeader('status', '400', true);
            $app->close();
        }

        $response = json_encode([
            'default_rate' => $defaultRate,
            'company_default_rate' => $defaultCompanyRate,
        ]);
        
        echo $response;
        
        // $app->setHeader('status', '200', true);
    
        $app->setHeader('Content-Type', 'application/json', true);
       // $app->setBody($response);
        $app->close();
    }
}
