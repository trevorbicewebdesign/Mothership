<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

\defined('_JEXEC') or die;

/**
 * Invoices for the payment's client, labelled "#number - account - $total".
 * Scoped to the selected client_id (like accountlist scopes to client).
 */
class InvoicelistField extends ListField
{
    protected $type = 'invoicelist';

    public function getOptions()
    {
        $data     = $this->form->getData();
        $clientId = (int) $data->get('client_id', 0);

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('i.id'),
                $db->quoteName('i.number'),
                $db->quoteName('i.total'),
                $db->quoteName('a.name', 'account_name'),
                $db->quoteName('c.name', 'client_name'),
            ])
            ->from($db->quoteName('#__mothership_invoices', 'i'))
            ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON ' . $db->quoteName('i.account_id') . ' = ' . $db->quoteName('a.id'))
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON ' . $db->quoteName('i.client_id') . ' = ' . $db->quoteName('c.id'))
            ->order($db->quoteName('i.number') . ' DESC');

        // Only this client's invoices (when a client is selected).
        if ($clientId > 0) {
            $query->where($db->quoteName('i.client_id') . ' = ' . $clientId);
        }

        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        // Payment need not be tied to an invoice (e.g. a retainer / credit).
        $options = [HTMLHelper::_('select.option', '', Text::_('COM_MOTHERSHIP_PAYMENT_NO_INVOICE'))];

        foreach ($rows as $r) {
            $who   = $r->account_name ?: ($r->client_name ?: '');
            $label = '#' . $r->number
                . ($who !== '' ? ' - ' . $who : '')
                . ' - $' . number_format((float) $r->total, 2);

            $options[] = HTMLHelper::_('select.option', $r->id, $label);
        }

        return array_merge(parent::getOptions(), $options);
    }
}
