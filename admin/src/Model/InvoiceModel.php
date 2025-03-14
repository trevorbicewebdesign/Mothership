<?php

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;

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

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_mothership.invoice', 'invoice', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_mothership.edit.invoice.data', []);

        if (empty($data)) {
            $data = $this->getItem();
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

    protected function onInvoiceOpened($invoice, $previousStatus)
    {
        
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
}
