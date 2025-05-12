<?php

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper; // Ensure this is the correct namespace for LogHelper
use TrevorBice\Component\Mothership\Administrator\Service\EmailService; // Ensure this is the correct namespace for EmailService

\defined('_JEXEC') or die;

class InvoiceModel extends AdminModel
{
    use VersionableModelTrait;

    public $typeAlias = 'com_mothership.invoice';

    protected function canDelete($record)
    {
        if (empty($record->id) || $record->state != -2) {
            return false;
        }

        if (!empty($record->catid)) {
            return $this->getCurrentUser()->authorise('core.delete', 'com_mothership.category.' . (int) $record->catid);
        }

        return parent::canDelete($record);
    }

    protected function canCheckin($record)
    {
        return $this->getCurrentUser()->authorise('core.manage', 'com_mothership');
    }

    protected function canEdit($record)
    {
        return $this->getCurrentUser()->authorise('core.edit', 'com_mothership');
    }

    public function canDeleteInvoice($record): bool
    {
        // Assume $record is an object or associative array with at least 'id' and 'status'
        $id = isset($record->id) ? (int) $record->id : (int) $record['id'];
        $status = isset($record->status) ? (int) $record->status : (int) $record['status'];

        if ($status !== 1) {
            // Not a draft invoice
            return false;
        }

        // Extra check: invoice shouldn't have any linked payments
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__mothership_invoice_payment'))
            ->where($db->quoteName('invoice_id') . ' = ' . $id);

        $invoicePaymentCount = (int) $db->setQuery($query)->loadResult();

        if ($invoicePaymentCount > 0) {
            // Something went wrong — drafts shouldn't have payments
            throw new \RuntimeException("Draft invoice ID {$id} has associated payments, which should not be possible.");
        }

        return true;
    }


    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_mothership.invoice', 'invoice', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $data = $app->getUserState('com_mothership.edit.invoice.data', []);

        if ((is_object($data) && isset($data->id) && empty($data->id)) || (is_array($data) && empty($data['id']))) {
            $data = $this->getItem();

            if (empty($data->id)) {
                $params = ComponentHelper::getParams('com_mothership');
                $defaultRate = $params->get('company_default_rate');
                if ($defaultRate !== null) {
                    $data->rate = $defaultRate;
                }
            }
        }

        $this->preprocessData('com_mothership.invoice', $data);
        return $data;
    }


    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && $item->id) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_invoice_items'))
            ->where($db->quoteName('invoice_id') . ' = ' . (int) $item->id)
            ->order($db->quoteName('ordering') . ' ASC');

            $db->setQuery($query);
            $item->items = $db->loadAssocList();
        }

        return $item;
    }

    protected function prepareTable($table)
    {
        $table->name = htmlspecialchars_decode($table->name, ENT_QUOTES);
    }

    /**
     * Triggered when an invoice transitions to "Opened".
     *
     * @param  \Joomla\CMS\Table\Table  $invoice         The invoice table object.
     * @param  int                      $previousStatus  The previous status ID.
     *
     * @return void
     */
    protected function onInvoiceOpened($invoice, int $previousStatus): void
    {
        // Log the event or trigger plugins here
        \Joomla\CMS\Log\Log::add(
            sprintf('Invoice #%d status changed from %d to Opened.', $invoice->id, $previousStatus),
            \Joomla\CMS\Log\Log::INFO,
            'com_mothership'
        );

        \Joomla\CMS\Factory::getApplication()->triggerEvent('onMothershipInvoiceOpened', [$invoice]);

        // SEnd the invoice template to the client
        EmailService::sendTemplate('invoice.user-opened', 
        'test.smith@mailinator.com', 
        'New Invoice Opened', 
        [
            'fname' => 'Trevor',
            'invoice_number' => 'INV-2045',
            'account_name' => 'Trevor Bice Webdesign',
            'account_center_url' => 'https://example.com/account',
            'invoice_due_date' => 'April 30, 2025',
            'pay_invoice_link' => 'https://example.com/pay?invoice=2045',
            'company_name' => 'Trevor Bice Webdesign',
            'company_address' => '123 Main St, San Francisco, CA',
            'company_address_1' => '123 Main St',
            'company_address_2' => 'Suite 100',
            'company_city' => 'San Francisco',
            'company_state' => 'CA',
            'company_zip' => '94111',
            'company_phone' => '(555) 555-5555',
            'company_email' => 'info@trevorbice.com',
        ]);

        // Optional: add history or record in a log table
        LogHelper::logInvoiceStatusOpened($invoice->id, $invoice->client_id, $invoice->account_id);
    }

    public function save($data)
    {
        $table = $this->getTable();
        $db = $this->getDbo();

        Log::add('Data received for saving: ' . json_encode($data), Log::DEBUG, 'com_mothership');

        $isNew = empty($data['id']);
        $previousStatus = null;

        if (!$isNew) {
            $existingTable = $this->getTable();
            $existingTable->load($data['id']);
            $previousStatus = (int) $existingTable->status;
            $newStatus = (int) $data['status'];
        
            if (!empty($existingTable->locked)) {
                $this->setError(JText::_('COM_MOTHERSHIP_ERROR_INVOICE_LOCKED'));
                return false;
            }
        }

        if($data['due_date'] == '') {
            $data['due_date'] = null;
        }

        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }

        if (empty($table->created)) {
            $table->created = Factory::getDate()->toSql();
        }

        if (!$table->check() || !$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        $invoiceId = $table->id;
        // 🔔 Trigger when status is set to Opened (1), unless previous was Late (2)
        if ( !$isNew && ($newStatus == 2 && $previousStatus !== 2)) {
            $this->onInvoiceOpened($table, $previousStatus);
            // Fill in the due date
            $table->due_date = Factory::getDate()->modify('+30 days')->format('Y-m-d');
            $table->locked = 1;
            $table->store();
        }
        

        // Delete existing items
        $db->setQuery(
            $db->getQuery(true)
                ->delete($db->quoteName('#__mothership_invoice_items'))
                ->where($db->quoteName('invoice_id') . ' = ' . (int)$invoiceId)
        )->execute();

        // Insert new items
        if (!empty($data['items'])) {
            $i = 0;
            foreach ($data['items'] as $index => $item) {
                $columns = ['invoice_id', 'name', 'description', 'hours', 'minutes', 'quantity', 'rate', 'subtotal', 'ordering'];
                $values = [
                    (int)$invoiceId,
                    $db->quote($item['name']),
                    $db->quote($item['description']),
                    (int)$item['hours'],
                    (int)$item['minutes'],
                    (float)$item['quantity'],
                    (float)$item['rate'],
                    (float)$item['subtotal'],
                    (int)$i + 1 // Assuming ordering starts from 1
                ];

                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__mothership_invoice_items'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(',', $values));

                $db->setQuery($query)->execute();
                $i++;
            }
        }

        // Set the new record ID into the model state
        $this->setState($this->getName() . '.id', $table->id);

        return true;
    }

    /**
     * Cancel editing by checking in the record.
     *
     * @param   int|null  $pk  The primary key of the record to check in. If null, it attempts to load it from the state.
     *
     * @return  bool  True on success, false on failure.
     */
    public function cancelEdit($pk = null)
    {
        // Use the provided primary key or retrieve it from the model state
        $pk = $pk ? $pk : (int) $this->getState($this->getName() . '.id');

        if ($pk) {
            $table = $this->getTable();
            if (!$table->checkin($pk)) {
                $this->setError($table->getError());
                return false;
            }
        }

        return true;
    }

    public function delete(&$pks)
    {
        $result = parent::delete($pks);

        if ($result) {
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__mothership_invoice_items'))
                ->where($db->quoteName('invoice_id') . ' IN (' . implode(',', array_map('intval', $pks)) . ')');

            $db->setQuery($query)->execute();
        }

        return $result;
    }

    public function lock($id)
    {
        $table = $this->getTable();
        $table->load($id);

        if ($table->locked) {
            return false; 
        }

        $table->locked = 1;
        return $table->store();
    }
    public function unlock($id)
    {
        $table = $this->getTable();
        $table->load($id);

        if (!$table->locked) {
            return false; 
        }

        $table->locked = 0;
        return $table->store();
    }
}
