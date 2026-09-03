<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_invoices
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Module\MothershipInvoices\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_mothership_invoices.
 *
 * "Open" invoices mirror the com_mothership InvoicesModel definitions:
 *   - invoice status 2 = Opened (1 Draft, 3 Cancelled, 4 Closed are excluded);
 *   - a payment counts toward the paid total only when its status is 2 (Completed);
 *   - an invoice is open when it has a positive total and a remaining balance.
 * Keep these in sync with InvoicesModel::getListQuery().
 */
class MothershipInvoicesHelper
{
    private const INVOICE_STATUS_OPENED = 2;
    private const PAYMENT_STATUS_COMPLETED = 2;

    /**
     * Get the open invoices, oldest first.
     *
     * @param   int  $limit  Maximum rows to return.
     *
     * @return  array
     */
    public function getOpenInvoices(int $limit = 5): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $this->baseQuery($db);

        $query->select(
            [
                $db->quoteName('i.id'),
                $db->quoteName('i.number'),
                $db->quoteName('i.total'),
                $db->quoteName('i.created'),
                $db->quoteName('c.name', 'client_name'),
                $db->quoteName('a.name', 'account_name'),
                'COALESCE(pay.total_paid, 0) AS ' . $db->quoteName('total_paid'),
                '(i.total - COALESCE(pay.total_paid, 0)) AS ' . $db->quoteName('balance'),
            ]
        )
            ->order($db->quoteName('i.due_date') . ' DESC');

        $db->setQuery($query, 0, max(1, $limit));

        try {
            return $db->loadObjectList() ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get a summary of all open invoices (count and total outstanding).
     *
     * @return  object  {count: int, outstanding: float}
     */
    public function getOpenSummary(): object
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $this->baseQuery($db);

        $query->select(
            [
                'COUNT(*) AS ' . $db->quoteName('count'),
                'COALESCE(SUM(i.total - COALESCE(pay.total_paid, 0)), 0) AS ' . $db->quoteName('outstanding'),
            ]
        );

        $db->setQuery($query);

        try {
            $row = $db->loadObject();
        } catch (\Exception $e) {
            $row = null;
        }

        return (object) [
            'count'       => (int) ($row->count ?? 0),
            'outstanding' => (float) ($row->outstanding ?? 0),
        ];
    }

    /**
     * Shared FROM / JOIN / WHERE for the open-invoice queries.
     *
     * @param   DatabaseInterface  $db  The database driver.
     *
     * @return  \Joomla\Database\DatabaseQuery
     */
    private function baseQuery(DatabaseInterface $db)
    {
        $query = $db->getQuery(true);

        $query->from($db->quoteName('#__mothership_invoices', 'i'))
            ->join(
                'LEFT',
                $db->quoteName('#__mothership_clients', 'c')
                . ' ON ' . $db->quoteName('i.client_id') . ' = ' . $db->quoteName('c.id')
            )
            ->join(
                'LEFT',
                $db->quoteName('#__mothership_accounts', 'a')
                . ' ON ' . $db->quoteName('i.account_id') . ' = ' . $db->quoteName('a.id')
            )
            ->join(
                'LEFT',
                '(SELECT ip.invoice_id, SUM(ip.applied_amount) AS total_paid'
                . ' FROM ' . $db->quoteName('#__mothership_invoice_payment', 'ip')
                . ' JOIN ' . $db->quoteName('#__mothership_payments', 'p') . ' ON ip.payment_id = p.id'
                . ' WHERE p.status = ' . (int) self::PAYMENT_STATUS_COMPLETED
                . ' GROUP BY ip.invoice_id) AS pay ON pay.invoice_id = i.id'
            )
            ->where($db->quoteName('i.status') . ' = ' . (int) self::INVOICE_STATUS_OPENED)
            ->where($db->quoteName('i.total') . ' > 0')
            ->where('(i.total - COALESCE(pay.total_paid, 0)) > 0.005');

        return $query;
    }
}
