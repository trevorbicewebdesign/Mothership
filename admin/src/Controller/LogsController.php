<?php
namespace TrevorBice\Component\Mothership\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class LogsController extends BaseController
{
    /**
     * Display the list of logs.
     *
     * @param   bool  $cachable   Should the view be cached
     * @param   array $urlparams  An array of safe url parameters and their variable types.
     *
     * @return  BaseController  A BaseController object to allow chaining.
     */
    public function display($cachable = false, $urlparams = [])
    {
        return parent::display($cachable, $urlparams);
    }

    /**
     * Delete the selected log entries.
     *
     * Logs have no dependants, so selected ids are deleted as given. (This was
     * previously copied from the clients controller: it treated log ids as client
     * ids and refused to delete any log whose id matched a client with accounts.)
     *
     * @return  void
     */
    public function delete()
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $ids = array_values(array_filter(array_map('intval', (array) $this->input->get('cid', [], 'array'))));

        if (!$app->getIdentity()->authorise('core.delete', 'com_mothership')) {
            $app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');
        } elseif (empty($ids)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_LOG_SELECTED'), 'warning');
        } elseif ($this->getModel('Logs')->delete($ids)) {
            $app->enqueueMessage(Text::plural('COM_MOTHERSHIP_LOGS_N_ITEMS_DELETED', count($ids)), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_LOG_DELETE_FAILED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=logs', false));
    }
}
