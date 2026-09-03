<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_invoiced
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Module\MothershipInvoiced\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_mothership_invoiced.
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    public function dispatch()
    {
        if (!$this->getApplication()->getIdentity()->authorise('core.manage', 'com_mothership')) {
            return;
        }

        parent::dispatch();
    }

    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        $helper = $this->getHelperFactory()->getHelper('MothershipInvoicedHelper');
        $year   = (int) date('Y');

        $data['year']        = $year;
        $data['monthly']     = $helper->getMonthlyTotals($year);
        $data['totalToDate'] = $helper->getTotalToDate();

        return $data;
    }
}
