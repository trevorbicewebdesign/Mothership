<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_invoiced
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Module\MothershipInvoiced\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_mothership_invoiced.
 *
 * Counts the amount invoiced (invoice total) by the invoice date (created).
 * Only *issued* invoices are included — Draft (status 1) and Cancelled (status 3)
 * are excluded; Opened (2) and Closed (4) count. Matches the invoice status codes
 * used across com_mothership.
 */
class MothershipInvoicedHelper
{
    /** WHERE clause fragment: issued invoices only. */
    private const ISSUED = 'status NOT IN (1, 3)';

    /**
     * Invoiced totals per month for a given year.
     *
     * @param   int  $year  Four-digit year.
     *
     * @return  array<int,float>  Month number (1-12) => total invoiced.
     */
    public function getMonthlyTotals(int $year): array
    {
        $out = array_fill(1, 12, 0.0);

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('MONTH(' . $db->quoteName('created') . ') AS ' . $db->quoteName('m'))
            ->select('COALESCE(SUM(' . $db->quoteName('total') . '), 0) AS ' . $db->quoteName('t'))
            ->from($db->quoteName('#__mothership_invoices'))
            ->where(self::ISSUED)
            ->where('YEAR(' . $db->quoteName('created') . ') = :year')
            ->group('MONTH(' . $db->quoteName('created') . ')')
            ->bind(':year', $year, ParameterType::INTEGER);

        $db->setQuery($query);

        try {
            foreach ($db->loadObjectList() ?: [] as $row) {
                $out[(int) $row->m] = (float) $row->t;
            }
        } catch (\Exception $e) {
            // fall through with zeros
        }

        return $out;
    }

    /**
     * Grand total invoiced to date (issued invoices).
     *
     * @return  float
     */
    public function getTotalToDate(): float
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('COALESCE(SUM(' . $db->quoteName('total') . '), 0)')
            ->from($db->quoteName('#__mothership_invoices'))
            ->where(self::ISSUED);

        $db->setQuery($query);

        try {
            return (float) $db->loadResult();
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}
