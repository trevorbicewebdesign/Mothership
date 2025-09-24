<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;

class PaymentModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('payment.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load the payment with status and related invoices
        $query = $db->getQuery(true)
            ->select([
                'p.*',

                // Interpreted status
                'CASE ' . $db->quoteName('p.status') .
                    ' WHEN 1 THEN ' . $db->quote('Pending') .
                    ' WHEN 2 THEN ' . $db->quote('Completed') .
                    ' WHEN 3 THEN ' . $db->quote('Failed') .
                    ' WHEN 4 THEN ' . $db->quote('Cancelled') .
                    ' WHEN 5 THEN ' . $db->quote('Refunded') .
                    ' ELSE ' . $db->quote('Unknown') .
                ' END AS status_text',

                // Related invoice info
                'inv.invoice_ids',
                'inv.invoice_numbers'
            ])
            ->from($db->quoteName('#__mothership_payments', 'p'))

            ->join(
                'LEFT',
                '(SELECT ip.payment_id,
                        GROUP_CONCAT(ip.invoice_id ORDER BY ip.invoice_id) AS invoice_ids,
                        GROUP_CONCAT(i.number ORDER BY ip.invoice_id) AS invoice_numbers
                FROM ' . $db->quoteName('#__mothership_invoice_payment', 'ip') . '
                JOIN ' . $db->quoteName('#__mothership_invoices', 'i') . ' ON ip.invoice_id = i.id
                GROUP BY ip.payment_id) AS inv
                ON inv.payment_id = p.id'
            )

            ->where('p.id = :id')
            ->where('p.status != -1')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $payment = $db->loadObject();

        return $payment;
    }


    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('payment.id', $id);
    }

    public function cancelPayment(int $paymentId): void
    {
        $db = $this->getDatabase();

        // Load the payment
        $payment = $this->getItem($paymentId);

        if (!$payment) {
            throw new \RuntimeException("Payment Not Found");
        }

        // Only pending (1) can be canceled
        if ((int) $payment->status !== 1) {
            throw new \RuntimeException("Only Pending Payments Can Be Canceled");
        }

        // Fetch any linked invoice BEFORE unlinking so we can log it
        $query = $db->getQuery(true)
            ->select($db->quoteName('invoice_id'))
            ->from($db->quoteName('#__mothership_invoice_payment'))
            ->where($db->quoteName('payment_id') . ' = ' . (int) $paymentId)
            ->setLimit(1);
        $invoiceId = (int) $db->setQuery($query)->loadResult();

        $db->transactionStart();

        try {
            // 1) Update status -> 3 (Canceled)
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__mothership_payments'))
                ->set($db->quoteName('status') . ' = 4')
                //->set($db->quoteName('modified') . ' = ' . $db->quote(Factory::getDate()->toSql()))
                ->where($db->quoteName('id') . ' = ' . (int) $paymentId);
            $db->setQuery($query)->execute();

            // 2) Unlink from invoice(s)
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__mothership_invoice_payment'))
                ->where($db->quoteName('payment_id') . ' = ' . (int) $paymentId);
            $db->setQuery($query)->execute();

            // 3) Log it
            $meta = [
                'invoice_id'     => $invoiceId ?: null,
                'amount'         => (float) $payment->amount,
                'payment_method' => (string) $payment->payment_method,
            ];

            // Get current user ID
            $user = Factory::getUser();
            $userId = $user ? (int) $user->id : 0;

            $params = [
                'client_id'   => isset($payment->client_id) ? (int) $payment->client_id : null,
                'account_id'  => isset($payment->account_id) ? (int) $payment->account_id : null,
                'object_type' => 'invoice',
                'object_id'   => $invoiceId ?: null,
                'action'      => 'canceled',
                'meta'        => $meta,
                'user_id'     => $userId,
            ];

            $result = LogHelper::log($params);

            $db->transactionCommit();
        } catch (\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
    }

}
