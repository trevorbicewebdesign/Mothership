<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipFrontPayByPaypalCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $mothershipConfig;
    private $joomlaUserData;
    private $invoiceItemData = [];

    const INVOICES_VIEW_ALL_URL = "index.php?option=com_mothership&view=invoices";
    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->mothershipConfig = $I->setMothershipConfig([
            'company_name' => 'Trevor Bice Webdesign',
            'company_address_1' => '370 Garden Lane',
            'company_city' => 'Bayside',
            'company_state' => 'California',
            'company_zip' => '95524',
            'company_phone' => '707-880-0156',

        ]);

        $this->joomlaUserData = $I->createJoomlaUser([], 10);

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
            'owner_user_id' => $this->joomlaUserData['id'],
        ]);

        $this->userData = $I->createMothershipUser([
            'user_id' => $this->joomlaUserData['id'],
            'client_id' => $this->clientData['id'],
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
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'hours' => '1',
            'minutes' => '30',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);

        $this->invoiceItemData[] = $I->createMothershipInvoiceItem([
            'invoice_id' => $this->invoiceData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'hours' => '1',
            'minutes' => '0',
            'quantity' => '1',
            'rate' => '70.00',
            'subtotal' => '70.00',
        ]);

        $I->amOnPage("/");
        $I->fillField(".mod-login input[name=username]", strtolower($this->joomlaUserData['username']));
        $I->fillField(".mod-login input[name=password]", '4&GoH#7FvPsY');
        $I->click(".mod-login button[type=submit]");
        $I->wait(1);
        $I->see("Hi {$this->joomlaUserData['name']},");
    }

    /**
     * @group frontend
     * @group payment
     * @group paypal
     */
    public function PayInvoiceWithPaypal(AcceptanceTester $I)
    {
        // Verify redirection to account center
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        $I->makeScreenshot("account-center-pay-invoice");

        $I->see("Pay", "table#invoicesTable tbody tr td:nth-child(8)");
        $I->click("Pay", "table#invoicesTable tbody tr td:nth-child(8)");
        $I->waitForText("Pay Invoice", 10, "h1");

        $I->waitForText("Pay Invoice #{$this->invoiceData['number']}", 10, "h1");
        // output the current url into the debug
        codecept_debug($I->grabFromCurrentUrl());
        $I->see("Pay Now");

        $paypal_fee = ($this->invoiceData['total'] * 0.0383) + 0.30;
        $total_with_fees = number_format($this->invoiceData['total'] + $paypal_fee,2);

        $I->see("Total Due: \${$this->invoiceData['total']}");
        // $I->see("\${$paypal_fee}");

        $I->click("#payment_method_0");

        $I->click("Pay Now");
        $I->waitForText("PayPal", 10, "h1");
        $I->fillFIeld("#email", "paypal_test@trevorbice.com");
        $I->fillField("#password", "1234qwer");
        $I->click("Next");
        $I->wait(3);
        $I->fillField("#password", "1234qwer");
        $I->click("Log In");
        $I->wait(4);
        $I->click("Pay Now");
        $I->wait(3);
        $I->see("You Paid \${$total_with_fees} USD");
        $I->click("Return to Merchant");
        $I->wait(3);

        $I->seeInDatabase("jos_mothership_payments", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'], 
            'amount' => $this->invoiceData['total'],
            'payment_method' => 'paypal',
            'fee_amount' => 0,
            'status' => 1,
        ]);

        $payment_id = $I->grabFromDatabase("jos_mothership_payments", "id", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'], 
            'amount' => $this->invoiceData['total'],
            'payment_method' => 'paypal',
            'fee_amount' => 0,
            'status' => 1,
        ]);

        $I->seeInDatabase("jos_mothership_invoice_payment", [
            'invoice_id' => $this->invoiceData['id'],
            'payment_id' => $payment_id, 
            'applied_amount' => $this->invoiceData['total'],
        ]);
        
        
        $I->seeInDatabase("jos_mothership_logs", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'user_id' => $this->joomlaUserData['id'],            
            'action' => 'initiated',
            'object_type' => 'payment',
            'object_id' => $this->accountData['id'], 
        ]);
        $meta = json_decode($I->grabFromDatabase("jos_mothership_logs", "meta", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'user_id' => $this->joomlaUserData['id'],            
            'action' => 'initiated',
            'object_type' => 'payment',
            'object_id' => $this->accountData['id'], 
        ]));
        codecept_debug($meta);
        $I->assertEquals($meta->invoice_id,  $this->invoiceData['id']);
        $I->assertEquals($meta->payment_method, "paypal");
        $I->assertEquals($meta->amount, $this->invoiceData['total']);
    }

}