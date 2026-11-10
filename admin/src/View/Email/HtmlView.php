<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Email;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use TrevorBice\Component\Mothership\Administrator\Model\EmailModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * One email as it was sent, or as it would have been.
 */
class HtmlView extends BaseHtmlView
{
    /** @var object The log entry, with the names the screen shows. */
    protected $item;

    public function display($tpl = null): void
    {
        /** @var EmailModel $model */
        $model      = $this->getModel();
        $this->item = $model->getItem();

        if (!$this->item) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EMAIL_NOT_FOUND'), 'error');
            $app->redirect(Route::_('index.php?option=com_mothership&view=emails', false));

            return;
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $canDo   = ContentHelper::getActions('com_mothership');
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_MOTHERSHIP_MANAGER_EMAIL_VIEW'), 'mail mothership-emails');

        if ($canDo->get('core.edit')) {
            $toolbar->standardButton('resend', 'COM_MOTHERSHIP_EMAIL_RESEND', 'emails.resend')
                ->icon('icon-mail')
                ->listCheck(false);
        }

        $toolbar->linkButton('close', 'JTOOLBAR_CLOSE')
            ->url(Route::_('index.php?option=com_mothership&view=emails', false))
            ->icon('icon-times');
    }
}
