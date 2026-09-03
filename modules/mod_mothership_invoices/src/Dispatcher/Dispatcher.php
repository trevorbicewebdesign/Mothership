<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_invoices
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Module\MothershipInvoices\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_mothership_invoices.
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Runs the dispatcher.
     *
     * @return  void
     */
    public function dispatch()
    {
        // Only show to users who may access the Mothership component.
        if (!$this->getApplication()->getIdentity()->authorise('core.manage', 'com_mothership')) {
            return;
        }

        parent::dispatch();
    }

    /**
     * Returns the layout data.
     *
     * @return  array
     */
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        $limit  = (int) $data['params']->get('limit', 5);
        $helper = $this->getHelperFactory()->getHelper('MothershipInvoicesHelper');

        $data['invoices'] = $helper->getOpenInvoices($limit);
        $data['summary']  = $helper->getOpenSummary();

        return $data;
    }
}
