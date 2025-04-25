<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;

class MothershipLogHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;
    protected $invoiceData;
    protected $paymentData;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/LogHelper.php';

        $this->tester->resetMothershipTables();

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $this->accountData = $this->tester->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->invoiceData = $this->tester->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => 175.00,
            'status' => '2',
        ]);

        $this->paymentData = $this->tester->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 175.00,
            'payment_date' => date('Y-m-d H:i:s'),
            'status' => '4',
            'payment_method' => 'manual'
        ]);
    }

    /**
     * @dataProvider paymentLifecycleProvider
     */
    public function testPaymentLifecycleLogs(string $event, callable $logMethod, string $expectedDescription, string $expectedDetails)
    {
        // Run the lifecycle logging method
        $logMethod();

        // Assert that the log was created as expected
        $this->tester->seeInDatabase('jos_mothership_logs', [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'object_type' => 'payment',
            'object_id' => $this->paymentData['id'],
            'action' => $event,
            'description' => $expectedDescription,
            'details' => $expectedDetails,
        ]);
    }

    public function paymentLifecycleProvider(): array
    {
        $clientId = $this->clientData['id'] ?? 1;
        $accountId = $this->accountData['id'] ?? 1;
        $invoiceId = $this->invoiceData['id'] ?? 1;
        $paymentId = $this->paymentData['id'] ?? 1;
        $total = $this->invoiceData['total'] ?? 175.00;
        $method = $this->paymentData['payment_method'] ?? 'manual';
        $invoicePadded = str_pad($invoiceId, 4, '0', STR_PAD_LEFT);

        return [
            'initiated' => [
                'initiated',
                function () use ($invoiceId, $paymentId, $clientId, $accountId, $total, $method) {
                    LogHelper::logPaymentInitiated($invoiceId, $paymentId, $clientId, $accountId, $total, $method);
                },
                "Payment initiated for Invoice #{$invoicePadded}",
                "A payment of \${$total} was initiated for Invoice #{$invoicePadded} using {$method}.",
            ],
            'completed' => [
                'completed',
                function () use ($invoiceId, $paymentId, $clientId, $accountId, $total, $method) {
                    LogHelper::logPaymentCompleted($invoiceId, $paymentId, $clientId, $accountId, $total, $method);
                },
                "Payment completed for Invoice #{$invoicePadded}",
                "A payment of \${$total} was completed for Invoice #{$invoicePadded} using {$method}.",
            ],
            'failed' => [
                'failed',
                function () use ($paymentId) {
                    LogHelper::logPaymentFailed($paymentId);
                },
                "Payment failed",
                "Payment ID: {$paymentId} failed.",
            ],
            'viewed' => [
                'viewed',
                function () use ($paymentId) {
                    LogHelper::logPaymentViewed($paymentId);
                },
                "Payment viewed",
                "Payment ID: {$paymentId} was viewed by `Test Smith`.",
            ]
        ];
    }
}
