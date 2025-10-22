<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\DomainHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;

\defined('_JEXEC') or die;


/**
 * Domain Controller for com_mothership
 */
class DomainController extends FormController
{
    protected $default_view = 'domain';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function whoisScan()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Domain');

        $domain = $model->getItem($input->getInt('id'));
        $domainName = $domain->name;

        // Check last_scan value and compare with current time
        $lastScan = strtotime($domain->last_scan);
        $currentTime = time();
        $timeDifference = $currentTime - $lastScan;
        // If the current scan was less than 1 hour ago, return an error message about the scan being too recent
        if ($timeDifference < 3600) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_DOMAIN_WHOIS_SCAN_TOO_RECENT', "<strong>{$domain->name}</strong>"), 'error');
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=domain&layout=edit&id={$domain->id}", false));
            return false;
        }

        try {
            $domainInfo = DomainHelper::scanDomain($domainName);
            
        } catch (\Exception $e) {
            echo json_encode(['error' => true, 'message' => $e->getMessage()]);
        }

        $domain->available = $domainInfo['available'];
        $domain->message = $domainInfo['message'];
        $domain->domain = $domainInfo['domain'] ?? null;
        $domain->registrar = $domainInfo['registrar'] ?? null;
        $domain->reseller = $domainInfo['reseller'] ?? null;

        $domain->purchase_date = isset($domainInfo['creation_date']) ? date('Y-m-d H:i:s', $domainInfo['creation_date']) : null;
        $domain->modified = isset($domainInfo['updated_date']) ? date('Y-m-d H:i:s', $domainInfo['updated_date']) : null;
        $domain->expiration_date = isset($domainInfo['expiration_date']) ? date('Y-m-d H:i:s', $domainInfo['expiration_date']) : null;

        $domain->epp_status = json_encode($domainInfo['epp_status']);

        $domain->ns1 = $domainInfo['name_servers'][0] ?? null;
        $domain->ns2 = $domainInfo['name_servers'][1] ?? null;
        $domain->ns3 = $domainInfo['name_servers'][2] ?? null;
        $domain->ns4 = $domainInfo['name_servers'][3] ?? null;

        $domain->dns_provider = $domainInfo['dns_provider'] ?? null;

        $domain->raw_text = $domainInfo['rawText'] ?? null;

        $domain->last_scan = date('Y-m-d H:i:s');

        $domainArray = is_object($domain) ? get_object_vars($domain) : (array) $domain;
        if (!$model->save($domainArray)) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_DOMAIN_WHOIS_SCAN_UPDATE_FAILED', "<strong>{$domain->name}</strong>"), 'message');
            $app->enqueueMessage($model->getError(), 'error');
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=domain&layout=edit&id={$domain->id}", false));
            return false;
        }

        LogHelper::logDomainScanned($domain->id, $domain->client_id, $domain->account_id);


        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_DOMAIN_WHOIS_SCANNED_SUCCESSFULLY', "<strong>{$domain->name}</strong>"), 'message');
        $this->setRedirect(Route::_("index.php?option=com_mothership&view=domain&layout=edit&id={$domain->id}", false));


        return false;
    }

    public function save($key = null, $urlVar = null)
    {
        $app   = Factory::getApplication();
        $input = $app->input;
        $data  = $input->get('jform', [], 'array');
        $model = $this->getModel('Domain');

        if (!$model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_DOMAIN_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');
            $id = !empty($data['id']) ? (int) $data['id'] : (int) $model->getState($model->getName() . '.id');
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=domain&layout=edit&id={$id}", false));
            return false;
        }

        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_DOMAIN_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        $task = $input->getCmd('task');
        $id   = !empty($data['id']) ? (int) $data['id'] : (int) $model->getState($model->getName() . '.id');

        // ✅ Check the item back in if we're leaving the edit screen
        if ($task !== 'apply' && $id) {
            // JModelAdmin provides checkin($pk) if the table has checked_out/checked_out_time
            if (method_exists($model, 'checkin')) {
                if (!$model->checkin($id)) {
                    // Not fatal, but let us know
                    $app->enqueueMessage(
                        Text::sprintf('JLIB_APPLICATION_CHECKIN_FAILED', htmlspecialchars($model->getError(), ENT_QUOTES, 'UTF-8')),
                        'warning'
                    );
                }
            }
        }

        // Clear any stale form data from session
        $app->setUserState('com_mothership.edit.domain.data', null);
        $app->setUserState('com_mothership.edit.domain.invalid', null);

        // Redirect
        if ($task === 'apply') {
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=domain&layout=edit&id={$id}", false));
        } else {
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=domains', false));
        }

        return true;
    }
    public function cancel($key = null)
    {
        $result = parent::cancel($key);

        $app = \Joomla\CMS\Factory::getApplication();
        $app->setUserState('com_mothership.edit.domain.data', null);
        $app->setUserState('com_mothership.edit.domain.invalid', null);

        $defaultRedirect = Route::_('index.php?option=com_mothership&view=domains', false);
        $redirect = MothershipHelper::getReturnRedirect($defaultRedirect);
        $this->setRedirect($redirect);

        return true;
    }

    public function delete()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Domain');
        $cid = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_DOMAIN_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_DOMAIN_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_DOMAIN_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect(MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=domains', false)));
    }
}