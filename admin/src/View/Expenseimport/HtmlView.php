<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Expenseimport;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiService;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * "New from PDF": upload a vendor's invoice or receipt. It is read and the new-expense form opens
 * filled in from it; the file is kept as the expense's receipt once the expense is saved.
 */
class HtmlView extends BaseHtmlView
{
    /** @var bool Whether the file will be read, or only kept. */
    public $aiEnabled = false;

    /** @var string The provider the file is sent to, for the notice. */
    public $provider = '';

    public function display($tpl = null): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.create', 'com_mothership')) {
            throw new \Joomla\CMS\Access\Exception\NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->aiEnabled = AiService::isEnabled();
        $this->provider  = ucfirst(AiService::providerName());

        ToolbarHelper::title(Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_TITLE'), 'coins mothership-expenses');
        $this->getDocument()->getToolbar()->linkButton('back', 'JTOOLBAR_CLOSE')
            ->url('index.php?option=com_mothership&view=expenses')
            ->icon('icon-times');

        parent::display($tpl);
    }
}
