<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_payments
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Module\MothershipPayments\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_mothership_payments.
 *
 * Only Completed payments (status 2) count, matching the com_mothership
 * BillingModel / PaymentsModel definitions. Keep in sync with those.
 */
class MothershipPaymentsHelper
{
    private const PAYMENT_STATUS_COMPLETED = 2;

    /**
     * Completed-payment totals per month for a given year.
     *
     * @param   int  $year  Four-digit year.
     *
     * @return  array<int,float>  Month number (1-12) => total.
     */
    public function getMonthlyTotals(int $year): array
    {
        $out = array_fill(1, 12, 0.0);

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('MONTH(' . $db->quoteName('payment_date') . ') AS ' . $db->quoteName('m'))
            ->select('COALESCE(SUM(' . $db->quoteName('amount') . '), 0) AS ' . $db->quoteName('t'))
            ->from($db->quoteName('#__mothership_payments'))
            ->where($db->quoteName('status') . ' = ' . (int) self::PAYMENT_STATUS_COMPLETED)
            ->where('YEAR(' . $db->quoteName('payment_date') . ') = :year')
            ->group('MONTH(' . $db->quoteName('payment_date') . ')')
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
     * Grand total of all Completed payments to date.
     *
     * @return  float
     */
    public function getTotalToDate(): float
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('COALESCE(SUM(' . $db->quoteName('amount') . '), 0)')
            ->from($db->quoteName('#__mothership_payments'))
            ->where($db->quoteName('status') . ' = ' . (int) self::PAYMENT_STATUS_COMPLETED);

        $db->setQuery($query);

        try {
            return (float) $db->loadResult();
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}
