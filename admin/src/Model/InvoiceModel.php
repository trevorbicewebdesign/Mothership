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
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__mothership_invoice_items'))
                ->where($db->quoteName('invoice_id') . ' = ' . (int) $item->id);

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

        // Versioning support: Store serialized items (optional)
        if (!empty($data['items'])) {
            $registry = new Registry();
            $registry->loadArray($data['items']);
            $table->items_json = (string) $registry;
            $table->store();
        }

        // Delete existing items
        $db->setQuery(
            $db->getQuery(true)
                ->delete($db->quoteName('#__mothership_invoice_items'))
                ->where($db->quoteName('invoice_id') . ' = ' . (int) $invoiceId)
        )->execute();

        // Insert new items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $columns = ['invoice_id', 'name', 'description', 'hours', 'minutes', 'quantity', 'rate', 'subtotal'];
                $values = [
                    $db->quote($invoiceId),
                    $db->quote($item['name']),
                    $db->quote($item['description']),
                    (float) $item['hours'],
                    (float) $item['minutes'],
                    (float) $item['quantity'],
                    (float) $item['rate'],
                    (float) $item['subtotal']
                ];

                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__mothership_invoice_items'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(',', $values));

                $db->setQuery($query)->execute();
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
}
