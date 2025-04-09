<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;
use Joomla\CMS\Factory;

class InvoicesModel extends ListModel
{
    public function getItems()
    {
        $user = \Joomla\CMS\Factory::getUser();
        $userId = $user->id;
        $clientId = \TrevorBice\Component\Mothership\Site\Helper\MothershipHelper::getUserClientId($userId);

        if (!$clientId) {
            $app = \Joomla\CMS\Factory::getApplication();
            $app->enqueueMessage("You do not have an associated client.", 'danger');
            return [];
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select([
            'i.*',
            'a.name AS account_name',

            // Lifecycle status
            'CASE ' . $db->quoteName('i.status') .
                ' WHEN 1 THEN ' . $db->quote('Draft') .
                ' WHEN 2 THEN ' . $db->quote('Opened') .
                ' WHEN 3 THEN ' . $db->quote('Cancelled') .
                ' WHEN 4 THEN ' . $db->quote('Closed') .
                ' ELSE ' . $db->quote('Unknown') . ' END AS status',

            // Payment summary
            'COALESCE(pay.total_paid, 0) AS total_paid',
            'pay.payment_ids',

            // Has pending payment
            '(SELECT COUNT(*) FROM ' . $db->quoteName('#__mothership_invoice_payment', 'ip2') . '
            JOIN ' . $db->quoteName('#__mothership_payments', 'p2') . ' ON ip2.payment_id = p2.id
            WHERE ip2.invoice_id = i.id AND p2.status = 1) AS has_pending_payment',

            // Payment status
            'CASE
                WHEN (SELECT COUNT(*) FROM ' . $db->quoteName('#__mothership_invoice_payment', 'ip2') . '
                    JOIN ' . $db->quoteName('#__mothership_payments', 'p2') . ' ON ip2.payment_id = p2.id
                    WHERE ip2.invoice_id = i.id AND p2.status = 1) > 0 THEN ' . $db->quote('Pending Confirmation') . '
                WHEN COALESCE(pay.total_paid, 0) <= 0 THEN ' . $db->quote('Unpaid') . '
                WHEN COALESCE(pay.total_paid, 0) < i.total THEN ' . $db->quote('Partially Paid') . '
                ELSE ' . $db->quote('Paid') . '
            END AS payment_status'
        ]);

        $query->from($db->quoteName('#__mothership_invoices', 'i'))
            ->join('LEFT', '#__mothership_accounts AS a ON i.account_id = a.id')

            // Join payment aggregation
            ->join(
                'LEFT',
                '(SELECT ip.invoice_id,
                        SUM(ip.applied_amount) AS total_paid,
                        GROUP_CONCAT(p.id ORDER BY p.payment_date) AS payment_ids
                FROM ' . $db->quoteName('#__mothership_invoice_payment', 'ip') . '
                JOIN ' . $db->quoteName('#__mothership_payments', 'p') . ' ON ip.payment_id = p.id
                WHERE p.status = 2
                GROUP BY ip.invoice_id) AS pay
                ON pay.invoice_id = i.id'
            )

            ->where($db->quoteName('i.status') . ' != 1')
            ->where($db->quoteName('i.client_id') . ' = :clientId')
            ->bind(':clientId', $clientId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);

        return $db->loadObjectList();
    }

}