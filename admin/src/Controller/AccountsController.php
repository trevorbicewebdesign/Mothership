<?php
namespace TrevorBice\Component\Mothership\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class AccountsController extends BaseController
{
    /**
     * Display the list of Accounts.
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
     * Check in selected account items.
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
            $model = $this->getModel('Accounts');
            if ($model->checkin($ids)) {
                // this uses sprint f to insert the number of items checked in into the message
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_ACCOUNT_CHECK_IN_SUCCESS', count($ids)), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ACCOUNT_CHECK_IN_FAILED'), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=accounts', false));
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
            $blocked = [];
            $allowed = [];

            foreach ($ids as $accountId) {
                $accountId = (int) $accountId;

                // Check for related invoices only
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from('#__mothership_invoices')
                    ->where('account_id = ' . $accountId);
                $db->setQuery($query);
                $invoiceCount = (int) $db->loadResult();

                if ($invoiceCount > 0) {
                    $blocked[] = $accountId;
                } else {
                    $allowed[] = $accountId;
                }
            }

            if (!empty($allowed)) {
                $model = $this->getModel('Accounts');
                if ($model->delete($allowed)) {
                    $app->enqueueMessage(
                        Text::sprintf('COM_MOTHERSHIP_ACCOUNT_DELETE_SUCCESS', count($allowed), count($allowed) === 1 ? '' : 's'),
                        'message'
                    );
                } else {
                    $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ACCOUNT_DELETE_FAILED'), 'error');
                }
            }

            if (!empty($blocked)) {
                $app->enqueueMessage(
                    Text::sprintf('COM_MOTHERSHIP_ACCOUNT_DELETE_HAS_DEPENDENCIES', implode(', ', $blocked)),
                    'warning'
                );
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=accounts', false));
    }


}
