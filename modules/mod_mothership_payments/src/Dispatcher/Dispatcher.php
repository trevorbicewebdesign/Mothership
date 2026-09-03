<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_payments
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Module\MothershipPayments\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_mothership_payments.
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    public function dispatch()
    {
        // Only show to users who may access the Mothership component.
        if (!$this->getApplication()->getIdentity()->authorise('core.manage', 'com_mothership')) {
            return;
        }

        parent::dispatch();
    }

    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        $helper = $this->getHelperFactory()->getHelper('MothershipPaymentsHelper');
        $year   = (int) date('Y');

        $data['year']        = $year;
        $data['monthly']     = $helper->getMonthlyTotals($year);
        $data['totalToDate'] = $helper->getTotalToDate();

        return $data;
    }
}
