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
     * Check in selected client items.
     *
     * @return  void
     */
    public function checkIn()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        // Get the list of IDs from the request.
        $ids = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $model = $this->getModel('Logs');
            if ($model->checkin($ids)) {
                // this uses sprint f to insert the number of items checked in into the message
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_CLIENT_CHECK_IN_SUCCESS', count($ids)), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_CHECK_IN_FAILED'), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=logs', false));
    }

    public function delete()
    {
        $app   = Factory::getApplication();
        $input = $app->input;
        $db    = Factory::getDbo();

        $ids = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $allowed = [];
            $blocked = [];

            foreach ($ids as $clientId) {
                $clientId = (int) $clientId;

                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__mothership_accounts'))
                    ->where($db->quoteName('client_id') . ' = ' . $clientId);
                $db->setQuery($query);
                $accountCount = (int) $db->loadResult();

                if ($accountCount > 0) {
                    $blocked[] = $clientId;
                } else {
                    $allowed[] = $clientId;
                }
            }

            if (!empty($allowed)) {
                $model = $this->getModel('Logs');

                if ($model->delete($allowed)) {
                    $app->enqueueMessage(
                        Text::sprintf('COM_MOTHERSHIP_CLIENT_DELETE_SUCCESS', count($allowed), count($allowed) === 1 ? '' : 's'),
                        'message'
                    );
                } else {
                    $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_DELETE_FAILED'), 'error');
                }
            }

            if (!empty($blocked)) {
                $app->enqueueMessage(
                    Text::sprintf('COM_MOTHERSHIP_CLIENT_DELETE_HAS_ACCOUNTS', implode(', ', $blocked)),
                    'warning'
                );
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=logs', false));
    }

}
