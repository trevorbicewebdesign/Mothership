<?php
namespace TrevorBice\Component\Mothership\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class ClientsController extends BaseController
{
    /**
     * Display the list of clients.
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
     * Check in selected client items.
     *
     * @return  void
     */
    public function checkin()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        // Get the list of IDs from the request.
        $ids = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $model = $this->getModel('Clients');
            if ($model->checkin($ids)) {
                // this uses sprint f to insert the number of items checked in into the message
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_CLIENT_CHECK_IN_SUCCESS', count($ids)), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_CHECK_IN_FAILED'), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=clients', false));
    }

    public function delete()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        $ids = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $db = Factory::getDbo();
            $blocked = [];

            foreach ($ids as $clientId) {
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__mothership_accounts'))
                    ->where($db->quoteName('client_id') . ' = ' . (int) $clientId);
                $db->setQuery($query);
                $accountCount = (int) $db->loadResult();

                if ($accountCount > 0) {
                    $blocked[] = $clientId;
                }
            }

            // If any clients had accounts, block deletion
            if (!empty($blocked)) {
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_CLIENT_DELETE_HAS_ACCOUNTS', implode(', ', $blocked)), 'error');
            } else {
                $model = $this->getModel('Clients');

                if ($model->delete($ids)) {
                    $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_CLIENT_DELETE_SUCCESS', count($ids)), 'message');
                } else {
                    $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_DELETE_FAILED'), 'error');
                }
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=clients', false));
    }


}
