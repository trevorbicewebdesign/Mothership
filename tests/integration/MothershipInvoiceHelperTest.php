<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;

class MothershipInvoiceHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;
    protected $invoiceData;
    protected $invoiceItemData = [];

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/EmailService.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/InvoiceHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/ClientHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/AccountHelper.php';
        

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Client',
            'owner_user_id' => 1,
        ]);

        $this->accountData = $this->tester->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->invoiceData = $this->tester->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '175.00',
            'number' => 1001,
            'due_date' => NULL,
            'created' => date('Y-m-d H:i:s'),
            'status' => 1,
        ]);

        $this->invoiceItemData[] = $this->tester->createMothershipInvoiceItem([
            'invoice_id' => $this->invoiceData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'hours' => '1',
            'minutes' => '30',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);
    }

    public function testGetInvoiceSuccess()
    {
        $invoice = InvoiceHelper::getInvoice($this->invoiceData['id']);

        codecept_debug($invoice);
        $this->assertIsObject($invoice);

        $criteria = [
            'id' => $invoice->id,
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_invoices', $criteria);
    }

    public function testGetInvoiceInvalidId()
    {
        try{
            InvoiceHelper::getInvoice(9999);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Invoice ID 9999 not found.', $e->getMessage());
        }
    }

    public function getStatusProvider()
    {
        return [
            [1, 'Draft'],
            [2, 'Opened'],
            [3, 'Cancelled'],
            [4, 'Closed'],
            [5, 'Unknown'],
        ];
    }

    /**
     * @dataProvider getStatusProvider
     */
    public function testGetStatus($status_id, $expected)
    {
        codecept_debug("Status ID is $status_id");
        $status = InvoiceHelper::getStatus($status_id);

        codecept_debug($status);
        $this->assertIsString($status);
        $this->assertEquals($expected, $status);
    }

    public function updateInvoiceStatusProvider()
    {
        return [
            [1, 'Draft'],
            [2, 'Opened'],
            [3, 'Canceled'],
            [4, 'Closed'],
        ];
    }

    /**
     * @dataProvider updateInvoiceStatusProvider
     */
    public function testUpdateInvoiceStatus($status_id)
    {
        $invoiceId = $this->invoiceData['id'];

        $this->tester->seeInDatabase('jos_mothership_invoices', [
            'id' => $invoiceId,
            'status' => 1,
        ]);

        $results = InvoiceHelper::updateInvoiceStatus((object) $this->invoiceData, $status_id);

        codecept_debug($results);
        $this->assertTrue($results);

        $criteria = [
            'id' => $invoiceId,
            'status' => $status_id,
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_invoices', $criteria);

    }

    public function testUpdateInvoiceStatusInvalidId()
    {
        try {
            $results = InvoiceHelper::updateInvoiceStatus((object) ['id'=>9999], 2);
        } catch (\Exception $e) {
            $this->assertStringContainsString("Invoice ID 9999 not found.", $e->getMessage());
        }
    }

    public function testsetInvoiceClosed()
    {
        $invoiceId = $this->invoiceData['id'];

        $this->tester->seeInDatabase('jos_mothership_invoices', [
            'id' => $invoiceId,
            'status' => 1,
        ]);

        $results = InvoiceHelper::setInvoiceClosed((object) $this->invoiceData);
        codecept_debug($results);

        $criteria = [
            'id' => $invoiceId,
            'status' => 4,
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_invoices', $criteria);

    }

    public function testGetInvoiceAppliedPayments()
    {
        $invoiceId = $this->invoiceData['id'];
        
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

        $invoice_payment_id = $this->tester->createMothershipInvoicePayment([
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'applied_amount' => 100.00,
        ]);

        $this->tester->seeInDatabase('jos_mothership_payments', [
            'id' => $paymentId,
        ]);

        $results = InvoiceHelper::getInvoiceAppliedPayments($invoiceId);
        codecept_debug($results);

        $criteria = [
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_invoice_payment', $criteria);

        $results = InvoiceHelper::getInvoiceAppliedPayments($invoiceId);
        codecept_debug($results);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    public function testSumInvoiceAppliedPaymentsIncompletePayment()
    {
        $invoiceId = $this->invoiceData['id'];
        
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

        $invoice_payment_id = $this->tester->createMothershipInvoicePayment([
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'applied_amount' => 100.00,
        ]);

        $this->tester->seeInDatabase('jos_mothership_payments', [
            'id' => $paymentId,
        ]);

        $results = InvoiceHelper::sumInvoiceAppliedPayments($invoiceId);
        codecept_debug($results);

        $this->assertIsFloat($results);
        $this->assertEquals(0.00, $results);
    }

    public function testSumInvoiceAppliedPaymentsSuccess()
    {
        $invoiceId = $this->invoiceData['id'];
        
        $paymentData = $this->tester->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 100.00,
            'payment_date' => date('Y-m-d H:i:s'),
            'fee_amount' => 6.00,
            'fee_passed_on' => 0,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 2,
        ]);
        $paymentId = $paymentData['id'];

        $invoice_payment_id = $this->tester->createMothershipInvoicePayment([
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'applied_amount' => 100.00,
        ]);

        $this->tester->seeInDatabase('jos_mothership_payments', [
            'id' => $paymentId,
        ]);

        $results = InvoiceHelper::sumInvoiceAppliedPayments($invoiceId);
        codecept_debug($results);

        $this->assertIsFloat($results);
        $this->assertEquals(100.00, $results);
    }

    public function invoiceStatusMatrixProvider()
    {
        return [
            // [invoiceTotal, payments, expectedStatus]
            [100.00, [], 2],               // Opened
            [100.00, [25.00], 2],          // Still Opened
            [100.00, [100.00], 4],         // Closed (fully paid)
            [100.00, [50.00, 30.00], 2],   // Still Opened
            [100.00, [120.00], 4],         // Closed (overpaid)
        ];
    }
    /**
     * @dataProvider invoiceStatusMatrixProvider
     */
    public function testRecalculateInvoiceStatusWithMatrix(float $invoiceTotal, array $paymentAmounts, int $expectedStatus)
    {
        $invoiceData = $this->tester->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => $invoiceTotal,
            'number' => uniqid('INV'),
            'status' => 0, // Default to unpaid
            'due_date' => date('Y-m-d'),
        ]);
        $invoiceId = $invoiceData['id'];

        // Insert all payments and link to the invoice
        foreach ($paymentAmounts as $amount) {
            $paymentData = $this->tester->createMothershipPayment([
                'client_id' => $this->clientData['id'],
                'account_id' => $this->accountData['id'],
                'amount' => $amount,
                'payment_date' => date('Y-m-d H:i:s'),
                'status' => 2,
            ]);
            $paymentId = $paymentData['id'];

            $invoicePaymentData = $this->tester->createMothershipInvoicePayment([
                'invoice_id' => $invoiceId,
                'payment_id' => $paymentId,
                'applied_amount' => $amount,
            ]);
            $invoicePaymentId = $invoicePaymentData['id'];
        }

        // Recalculate and assert
        $new_status = InvoiceHelper::recalculateInvoiceStatus($invoiceId);
        codecept_debug("New Status is {$new_status}");

        $this->assertEquals($expectedStatus, $new_status);
    }

    public function isLateProvider()
    {
        $tz = new \DateTimeZone('America/Los_Angeles');
        $now = new \DateTimeImmutable('now', $tz);

        return [
            'due now (exact)' => [
                'due_date' => $now->format('Y-m-d H:i:s'),
                'expectedResult' => false,
            ],
            'future due date (in 30 minutes)' => [
                'due_date' => $now->modify('+30 minutes')->format('Y-m-d H:i:s'),
                'expectedResult' => false,
            ],
            'Due now (exact)' => [
                'due_date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'expectedResult' => false,
            ],
            'Future due date (in 30 minutes)' => [
                'due_date' => (new \DateTime('+30 minutes'))->format('Y-m-d H:i:s'),
                'expectedResult' => false,
            ],
            'Future due date (in 23 hours)' => [
                'due_date' => (new \DateTime('+23 hours'))->format('Y-m-d H:i:s'),
                'expectedResult' => false,
            ],
            'Future due date (in 1 day)' => [
                'due_date' => (new \DateTime('+1 day'))->format('Y-m-d H:i:s'),
                'expectedResult' => false,
            ],
            'No due date (null)' => [
                'due_date' => null,
                'expectedResult' => false,
            ],
        ];
    }


    /**
     * @dataProvider isLateProvider
     */
    public function testIsLate($due_date, $expectedResult)
    {
        $invoiceData = $this->tester->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => 100.00,
            'number' => '1000',
            'status' => 2,
            'due_date' => $due_date,
        ]);

        $invoiceId = $invoiceData['id'];
        $result = InvoiceHelper::isLate($invoiceId);

        codecept_debug([
            'Due date' => $due_date,
            'Expected' => $expectedResult,
            'Actual' => $result,
        ]);

        $this->assertEquals($expectedResult, $result);
    }

    public function dueStringProvider()
    {
        return [
        [date('Y-m-d H:i:s', strtotime('+1 day')), 'Due in 1 day'],
        [date('Y-m-d H:i:s', strtotime('+5 days')), 'Due in 5 days'],
        [date('Y-m-d H:i:s', strtotime('-1 day')), '1 day late'],
        [date('Y-m-d H:i:s', strtotime('-5 days')), '5 days late'],
        [date('Y-m-d H:i:s', strtotime('+1 hour')), 'Due in 1 hour'],
        [date('Y-m-d H:i:s', strtotime('+5 hours')), 'Due in 5 hours'],
        [date('Y-m-d H:i:s', strtotime('-1 hour')), '1 hour late'],
        [date('Y-m-d H:i:s', strtotime('-5 hours')), '5 hours late'],
        [null, 'No due date'],
        ];
    }

    /**
     * @dataProvider dueStringProvider
     */
    public function testGetDueStringFromDate($due_date, $expectedResult)
    {
        $results = InvoiceHelper::getDueStringFromDate($due_date);
        codecept_debug($results);

        $this->assertIsString($results);
        $this->assertStringContainsString($expectedResult, $results);
    }
}