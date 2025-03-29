<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\PaymentHelper;

class MothershipPaymentHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/AccountHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/ClientHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/InvoiceHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/PaymentHelper.php';

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Client',
            'email' => 'test.smith@mailinator.com',
            'owner_user_id' => 1,
        ]);

        $this->accountData = $this->tester->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

    }

    public function testInsertPaymentRecordSuccess()
    {
        $paymentId = PaymentHelper::insertPaymentRecord(
            $this->clientData['id'], // $client_id,
            $this->accountData['id'], // $account_id,
            100.00, // $amount,
            date('Y-m-d H:i:s'), // $date
            6.00, // $fee_amount,
            false, // $fee_passed_on,
            'paypal', // $payment_method,
            '123456', // $transaction_id,
            1, // $payment_status,
        );

        codecept_debug($paymentId);
        $this->assertIsInt($paymentId);
        $this->assertGreaterThan(0, $paymentId);

        $criteria = [
            'id' => $paymentId,
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 100.00,
            'payment_date' => date('Y-m-d H:i:s'),
            'fee_amount' => 6.00,
            'fee_passed_on' => 0,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 1,
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_payments', $criteria);
    }

    public function testInsertPaymentRecordInvalidClient()
    {
        try {
            $results = PaymentHelper::insertPaymentRecord(
                9999, // $client_id,
                $this->accountData['id'], // $account_id,
                100.00, // $amount,
                date('Y-m-d H:i:s'), // $date
                6.00, // $fee_amount,
                false, // $fee_passed_on,
                'paypal', // $payment_method,
                '123456', // $transaction_id,
                1, // $payment_status,
            );
        } catch (\Exception $e) {
            $this->assertEquals('Client ID 9999 not found.', $e->getMessage());
            return;
        }
    }

    public function testInsertPaymentRecordInvalidAccount()
    {
        try {
            $results = PaymentHelper::insertPaymentRecord(
                $this->clientData['id'], // $client_id,
                9999, // $account_id,
                100.00, // $amount,
                date('Y-m-d H:i:s'), // $date
                6.00, // $fee_amount,
                false, // $fee_passed_on,
                'paypal', // $payment_method,
                '123456', // $transaction_id,
                1, // $payment_status,
            );
        } catch (\Exception $e) {
            $this->assertEquals('Account ID 9999 not found.', $e->getMessage());
            return;
        }
    }

    public function testInsertPaymentRecordInvalidAmount()
    {
        try {
            $results = PaymentHelper::insertPaymentRecord(
                $this->clientData['id'], // $client_id,
                9999, // $account_id,
                0, // $amount,
                date('Y-m-d H:i:s'), // $date
                6.00, // $fee_amount,
                false, // $fee_passed_on,
                'paypal', // $payment_method,
                '123456', // $transaction_id,
                1, // $payment_status,
            );
        } catch (\Exception $e) {
            $this->assertEquals('Invalid amount', $e->getMessage());
            return;
        }
    }

    public function getStatusProvider()
    {
        return [
            [1, 'Pending'],
            [2, 'Completed'],
            [3, 'Failed'],
            [4, 'Cancelled'],
            [5, 'Refunded'],
            [6, 'Unknown'],
        ];
    }

    /**
     * @dataProvider getStatusProvider
     */
    public function testGetStatus($status_id, $expected)
    {
        codecept_debug("Status ID is $status_id");
        $status = PaymentHelper::getStatus($status_id);

        codecept_debug($status);
        $this->assertIsString($status);
        $this->assertEquals($expected, $status);
    }

    
    public function testInsertInvoicePayments()
    {
        $invoiceData = $this->tester->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => 100.00,
            'number' => '1000',
            'status' => 2,
            'due_date' => date('Y-m-d', strtotime('-1 day')),
        ]);
        $invoiceId = $invoiceData['id'];
        $paymentData = $this->tester->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 100.00,
            'payment_date' => date('Y-m-d H:i:s'),
            'fee_amount' => 6.00,
            'fee_passed_on' => 0,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 1,
        ]);
        $paymentId = $paymentData['id'];

        $this->tester->seeInDatabase('jos_mothership_payments', [
            'id' => $paymentId,
        ]);

        $results = PaymentHelper::insertInvoicePayments($invoiceId, $paymentId, 100.00);
        codecept_debug($results);

        $this->assertIsInt($results);
        $this->assertNotFalse($results);

        $criteria = [
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'applied_amount' => 100.00,
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_invoice_payment', $criteria);
    }
    
    public function testInsertInvoicePaymentBadInvoiceId()
    {
        $invoiceData = $this->tester->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => 100.00,
            'number' => '1000',
            'status' => 2,
            'due_date' => date('Y-m-d', strtotime('-1 day')),
        ]);
        $invoiceId = $invoiceData['id'];
        $paymentData = $this->tester->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 100.00,
            'payment_date' => date('Y-m-d H:i:s'),
            'fee_amount' => 6.00,
            'fee_passed_on' => 0,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 1,
        ]);
        $paymentId = $paymentData['id'];

        $this->tester->seeInDatabase('jos_mothership_payments', [
            'id' => $paymentId,
        ]);

        try {
            $results = PaymentHelper::insertInvoicePayments(9999, $paymentId, 100.00);
            codecept_debug($results);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Invoice ID 9999 not found.', $e->getMessage());
            return;
        }

    }
}