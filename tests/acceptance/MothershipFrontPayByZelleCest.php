<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipFrontPayByZelleCest
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
            'company_name' => 'Your Company Name',
            'company_address' => '123 Nowhere St, CA, 12345',
            'company_address_1' => '123 Nowhere St',
            'company_address_2' => '',
            'company_city' => 'Nowhere',
            'company_state' => 'California',
            'company_zip' => '12345',
            'company_phone' => '555 555-5555',

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
     * @group zelle
     * @group payment-end-to-end
     */
    public function PayInvoiceWithZelle(AcceptanceTester $I)
    {
        $I->updateInDatabase("jos_extensions", [
            'params' => '{"display_name":"Zelle","zelle_email":"test.smith@mailinator.com","zelle_phone":"555 555-5555","instructions":""}',
        ], [
            'name' => 'COM_MOTHERSHIP_ZELLE_PLUGIN',
        ]);
        $I->updateInDatabase("jos_extensions", [
            'params' => '{"display_name":"Pay By Check","checkpayee":"Your Company Name"}',
        ], [
            'name' => 'COM_MOTHERSHIP_PAYBYCHECK_PLUGIN',
        ]);
        // Verify redirection to account center
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        $I->see("Pay", "table#invoicesTable tbody tr td:nth-child(8)");
        $I->click("Pay", "table#invoicesTable tbody tr td:nth-child(8)");
        $I->wait(1);
        $I->waitForText("Pay Invoice", 10, "h1");
        $I->waitForText("Pay Invoice #{$this->invoiceData['number']}", 10, "h1");
        $I->makeScreenshot("account-center-pay-invoice");
        codecept_debug($I->grabFromCurrentUrl()); // output the current url into the debug
        $I->see("Pay Now");
        $I->see("Total Due: \${$this->invoiceData['total']}");
        // Click Pay By Check
        $I->click("#payment_method_1");
        $I->makeScreenshot("account-center-pay-invoice-paybycheck-instructions");
        $I->click("Pay Now");
        $I->wait(1);
        $I->waitForText("Thank You", 10, "h1");
        $I->makeScreenshot("account-center-pay-invoice-zelle-thank-you");
        // Once the user clicks `Pay Now` the payment is created and the user is redirected to the thank you page
        // The Admin should receive an email regarding the pending payment
        $I->getEmailBySubject("New Pending Payment for zelle");

        $I->seeInDatabase("jos_mothership_payments", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'], 
            'amount' => $this->invoiceData['total'],
            'payment_method' => 'zelle',
            'fee_amount' => 0,
            'status' => 1,
        ]);

        $payment_id = $I->grabFromDatabase("jos_mothership_payments", "id", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'], 
            'amount' => $this->invoiceData['total'],
            'payment_method' => 'zelle',
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
        $I->assertEquals($meta->payment_method, "zelle");
        $I->assertEquals($meta->amount, $this->invoiceData['total']);

        $I->click("Return to Payments");
        $I->wait(1);
        $I->waitForText("Payments", 10, "h1");
    }

}