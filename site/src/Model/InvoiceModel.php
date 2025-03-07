<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class InvoiceModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('invoice.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load the invoice
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__mothership_invoices')
            ->where('id = ' . (int) $id)
            ->where('status != -1');
        $db->setQuery($query);
        $invoice = $db->loadObject();

        if ($invoice) {
            // Load related items
            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__mothership_invoice_items')
                ->where('invoice_id = ' . (int) $invoice->id);
            $db->setQuery($query);
            $invoice->items = $db->loadAssocList();
        }

        return $invoice;
    }

    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('invoice.id', $id);
    }

}
