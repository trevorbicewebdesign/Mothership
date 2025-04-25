<?php
namespace Tests\Api;

use Tests\Support\ApiTester;

class PaypalNotificationCest
{
    private $clientData;
    private $accountData;
    private $userData;
    private $invoiceData;
    private $invoiceItemData =[];
    private $paymentData;

    const PAYPAL_NOTIFY_URL = '/index.php?option=com_mothership&controller=payment&task=pluginTask&plugin=paypal&action=notify&id=%d&invoice_id=%d';

    public function _before(ApiTester $I)
    {
        $I->resetMothershipTables();
        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->invoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '100.00',
            'number' => '1000',
            'status' => 2,
        ]);

        $this->invoiceItemData[] = $I->createMothershipInvoiceItem([
            'invoice_id' => $this->invoiceData['id'],
            'description' => 'Test Item',
            'subtotal' => '100.00',
            'quantity' => 1,
        ]);
        $this->invoiceItemData[][] = $I->createMothershipInvoiceItem([
            'invoice_id' => $this->invoiceData['id'],
            'description' => 'Test Item 2',
            'subtotal' => '50.00',
            'quantity' => 2,
        ]);


        $this->paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => '100.00',
            'payment_method' => 'paypal',
            'status' => 1,
        ]);


    }
    public function testPaypalNotification(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');

        $url = sprintf(self::PAYPAL_NOTIFY_URL, $this->paymentData['id'], $this->invoiceData['id']);
        codecept_debug($url);

        $I->sendPOST($url, [
            // Example IPN fields:
            'custom' => $this->invoiceData['id'],
            'txn_id' => '1234567890ABCDEF',
            'payment_status' => 'Completed',
            'mc_gross' => '103.20',
            'mc_currency' => 'USD',
            'mc_fee' => '3.20',
            'payer_email' => 'buyer@example.com',
        ]);

        $I->seeResponseCodeIsSuccessful();

        $I->seeResponseContains('IPN Received');
        $I->assertInvoiceStatusPaid($this->invoiceData['id']);
        $I->assertPaymentStatusCompleted( $this->paymentData['id'] );

        $I->seeInDatabase("jos_mothership_payments", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'], 
            'id' => $this->paymentData['id'],
            'amount' => '103.20',
            'fee_amount' => '3.20',
            'fee_passed_on' => 0,
            'payment_method' => 'paypal',
            // 'transaction_id' => '1234567890ABCDEF',
            'status' => 2,
        ]);

        $I->seeInDatabase("jos_mothership_invoice_payment", [
            'invoice_id' => $this->invoiceData['id'],
            'payment_id' => $this->paymentData['id'],
            'applied_amount' => '103.2',
        ]);
        
        $I->seeInDatabase("jos_mothership_logs", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            // user_id' => $this->joomlaUserData['id'],            
            'action' => 'completed',
            'object_type' => 'payment',
            'object_id' => $this->accountData['id'], 
        ]);

        $meta = json_decode($I->grabFromDatabase("jos_mothership_logs", "meta", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            // 'user_id' => $this->joomlaUserData['id'],            
            'action' => 'completed',
            'object_type' => 'payment',
            'object_id' => $this->accountData['id'], 
        ]));

        codecept_debug($meta);

    }

    /**
     * Same txn_id used in two IPNs; ensure only one payment is created.
     */
    public function testDuplicateTransactionId(ApiTester $I)
    {
        $url = sprintf(self::PAYPAL_NOTIFY_URL, $this->paymentData['id'], $this->invoiceData['id']);

        $I->sendPOST($url, [
            'custom' => $this->invoiceData['id'],
            'txn_id' => 'TXNDUPLICATE123',
            'payment_status' => 'Completed',
            'mc_gross' => '103.20',
            'mc_currency' => 'USD',
            'mc_fee' => '3.20',
            'payer_email' => 'buyer@example.com',
        ]);
        $I->seeResponseCodeIsSuccessful();
        $paymentId1 = $I->grabLastCompletedPaymentId();

        $I->sendPOST($url, [
            'custom' => $this->invoiceData['id'],
            'txn_id' => 'TXNDUPLICATE123', // same ID
            'payment_status' => 'Completed',
            'mc_gross' => '103.20',
            'mc_currency' => 'USD',
            'mc_fee' => '3.20',
            'payer_email' => 'buyer@example.com',
        ]);
        $I->seeResponseCodeIsSuccessful();
        $paymentId2 = $I->grabLastCompletedPaymentId();

        $I->assertEquals($paymentId1, $paymentId2, 'Duplicate txn_id should not create a new payment');
    }

    /**
     * Invalid invoice id in request; expect failure.
     */
    public function testInvalidInvoiceId(ApiTester $I)
    {
        $url = sprintf(self::PAYPAL_NOTIFY_URL, $this->paymentData['id'], "9999");

        $I->sendPOST($url, [
            'custom' => 9999,
            'txn_id' => 'TXN12345INVALID',
            'payment_status' => 'Completed',
            'mc_gross' => '103.20',
            'mc_currency' => 'USD',
            'mc_fee' => '3.20',
            'payer_email' => 'buyer@example.com',
        ]);

        $I->seeResponseCodeIs(500);
        $I->dontSeeInDatabase('jos_mothership_payments', ['transaction_id' => 'TXN12345INVALID']);
    }

    /**
     * Payment status = Pending should not mark invoice as paid.
     */
    /*
    public function testPendingPaymentStatus(ApiTester $I)
    {
        $url = sprintf(self::PAYPAL_NOTIFY_URL, $this->invoiceData['id']);

        $I->sendPOST($url, [
            'custom' => $this->invoiceData['id'],
            'txn_id' => 'TXNPENDING123',
            'payment_status' => 'Pending',
            'mc_gross' => '103.20',
            'mc_currency' => 'USD',
            'mc_fee' => '3.20',
            'payer_email' => 'buyer@example.com',
        ]);

        $I->seeResponseContains('Payment pending');
        $I->assertInvoiceStatusOpen($this->invoiceData['id']);
    }
    */

    /**
     * Failed payment should not result in a recorded payment or invoice update.
     */
    /*
    public function testFailedPaymentStatus(ApiTester $I)
    {
        $url = sprintf(self::PAYPAL_NOTIFY_URL, $this->invoiceData['id']);

        $I->sendPOST($url, [
            'custom' => $this->invoiceData['id'],
            'txn_id' => 'TXNFAIL123',
            'payment_status' => 'Failed',
            'mc_gross' => '103.20',
            'mc_currency' => 'USD',
            'mc_fee' => '3.20',
            'payer_email' => 'buyer@example.com',
        ]);

        $I->seeResponseContains('Payment failed');
        $I->assertInvoiceStatusOpen($this->invoiceData['id']);
        $I->dontSeeInDatabase('jos_mothership_payments', ['transaction_id' => 'TXNFAIL123']);
    }
    */
    /**
     * Missing txn_id field should result in error.
     */
    /*
    public function testMissingTxnIdField(ApiTester $I)
    {
        $url = sprintf(self::PAYPAL_NOTIFY_URL, $this->invoiceData['id']);

        $I->sendPOST($url, [
            'custom' => $this->invoiceData['id'],
            'payment_status' => 'Completed',
            'mc_gross' => '103.20',
            'mc_currency' => 'USD',
            'mc_fee' => '3.20',
            'payer_email' => 'buyer@example.com',
        ]);

        $I->seeResponseCodeIs(500);
        $I->dontSeeInDatabase('jos_mothership_payments', ['amount' => '103.20']);
    }
    */
    /**
     * Custom does not match invoice param; should not apply payment.
     */
    /*
    public function testMismatchedCustomAndInvoice(ApiTester $I)
    {
        $invoiceId = $this->invoiceData['id'];
        $badCustom = $invoiceId + 100;

        $url = sprintf(self::PAYPAL_NOTIFY_URL, $invoiceId);

        $I->sendPOST($url, [
            'custom' => $badCustom,
            'txn_id' => 'TXNMISMATCH123',
            'payment_status' => 'Completed',
            'mc_gross' => '103.20',
            'mc_currency' => 'USD',
            'mc_fee' => '3.20',
            'payer_email' => 'buyer@example.com',
        ]);

        $I->seeResponseCodeIs(500);
        $I->dontSeeInDatabase('jos_mothership_payments', ['transaction_id' => 'TXNMISMATCH123']);
    }
    */
    /**
     * Negative mc_gross should be rejected.
     */
    /*
    public function testNegativeGrossAmount(ApiTester $I)
    {
        $url = sprintf(self::PAYPAL_NOTIFY_URL, $this->invoiceData['id']);

        $I->sendPOST($url, [
            'custom' => $this->invoiceData['id'],
            'txn_id' => 'TXNNEGATIVE123',
            'payment_status' => 'Completed',
            'mc_gross' => '-103.20',
            'mc_currency' => 'USD',
            'mc_fee' => '3.20',
            'payer_email' => 'buyer@example.com',
        ]);

        $I->seeResponseCodeIs(500);
        $I->dontSeeInDatabase('jos_mothership_payments', ['transaction_id' => 'TXNNEGATIVE123']);
    }
    */
}