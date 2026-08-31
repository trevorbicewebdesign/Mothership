<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class TicketsController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        return parent::display($cachable, $urlparams);
    }

    public function delete()
    {
        // CSRF protection.
        $this->checkToken();

        $app   = Factory::getApplication();
        $input = $app->getInput();

        // ACL: deleting tickets requires core.delete on the component.
        if (!$app->getIdentity()->authorise('core.delete', 'com_mothership')) {
            throw new \Joomla\CMS\Access\Exception\NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $ids = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $model = $this->getModel('Tickets');
            if ($model->delete($ids)) {
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_TICKET_DELETE_SUCCESS', count($ids)), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_TICKET_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=tickets', false));
    }
}
