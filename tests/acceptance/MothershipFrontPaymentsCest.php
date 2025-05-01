<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipFrontPaymentsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $mothershipConfig;
    private $joomlaUserData;
    private $paymentData;
    private $invoicePaymentData;

    private $invoiceItemData = [];
    const PAYMENTS_VIEW_ALL_URL= "index.php?option=com_mothership&view=payments";
    const PAYMENT_VIEW_URL= "index.php?option=com_mothership&view=payment&id=%s";
    const PAYMENTS_VIEW_ALL_SEF_URL = "/account-center/billing/payments";
    const PAY_INVOICE_URL= "index.php?option=com_mothership&controller=payments&task=processPayment&id=%s&pactiontype=processPayment";

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
            'testmode' => 1,
        ]);

        

        $this->joomlaUserData = $I->createJoomlaUser([
            'name' => 'Test Client',
        ], 8);

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
            'due_date' => date('Y-m-d', strtotime('-1 day')),
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

        $this->paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => '100.00',
            'payment_date' => date('Y-m-d H:i:s'),
            'fee_amount' => '6.00',
            'fee_passed_on' => 0,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 1,
        ]);

        $this->invoicePaymentData = $I->createMothershipInvoicePayment([
            'invoice_id' => $this->invoiceData['id'],
            'payment_id' => $this->paymentData['id'],
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
     */
    public function ViewAllPaymentsPage(AcceptanceTester $I)
    {
        // Verify redirection to account center
        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Payments", 10, "h1");
        $I->makeScreenshot("account-center-view-all-payments");

        // Confirm the correct number of records
        $I->seeNumberOfElements("main table tbody tr", 1);

        $I->see("Payment Status Legend", ".mt-4");
        $I->see("Pending", ".mt-4 ul.mb-0 li:nth-child(1)");
        $I->see("Completed", ".mt-4 ul.mb-0 li:nth-child(2)");
        $I->see("Failed", ".mt-4 ul.mb-0 li:nth-child(3)");
        $I->see("Cancelled", ".mt-4 ul.mb-0 li:nth-child(4)");
        $I->see("Refunded", ".mt-4 ul.mb-0 li:nth-child(5)");
        $I->seeNumberOfElements(".mt-4 ul.mb-0 li", 5);

        // Confirm the table headers
        $I->see("#", "main table thead tr th:nth-child(1)");
        $I->see("Account", "main table thead tr th:nth-child(2)");
        $I->see("Amount", "main table thead tr th:nth-child(3)");
        $I->see("Status", "main table thead tr th:nth-child(4)");
        $I->see("Fee Amount", "main table thead tr th:nth-child(5)");        
        $I->see("Payment Method", "main table thead tr th:nth-child(6)");
        $I->see("Transaction Id", "main table thead tr th:nth-child(7)");
        $I->see("Invoices", "main table thead tr th:nth-child(8)");

        // Confirm the table data
        $I->see("Test Account", "main table tbody tr:nth-child(1) td:nth-child(2)");
        $I->see("100.00", "main table tbody tr:nth-child(1) td:nth-child(3)");
        $I->see("Pending", "main table tbody tr:nth-child(1) td:nth-child(4)");
        $I->see("6.00", "main table tbody tr:nth-child(1) td:nth-child(5)");        
        $I->see("PayPal", "main table tbody tr:nth-child(1) td:nth-child(6)");
        $I->see("123456", "main table tbody tr:nth-child(1) td:nth-child(7)");
        $I->see("{$this->invoiceData['id']}", "main table tbody tr:nth-child(1) td:nth-child(8)");
        $I->see("{$this->invoiceData['id']}", "main table tbody tr:nth-child(1) td:nth-child(8)");

        $invoiceUrl = $I->grabAttributeFrom("main table tbody tr:nth-child(1) td:nth-child(8) a", "href");
        $I->seeLink("{$this->invoiceData['id']}", $invoiceUrl);

        $I->setPaymentStatus($this->paymentData['id'], 2);
        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Payments", 10, "h1");

        $I->see("Completed", "main table tbody tr:nth-child(1) td:nth-child(4)");

        $I->setPaymentStatus($this->paymentData['id'], 3);
        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Payments", 10, "h1");

        $I->see("Failed", "main table tbody tr:nth-child(1) td:nth-child(4)");

        $I->setPaymentStatus($this->paymentData['id'], 4);
        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Payments", 10, "h1");

        $I->see("Cancelled", "main table tbody tr:nth-child(1) td:nth-child(4)");

        $I->setPaymentStatus($this->paymentData['id'], 5);
        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Payments", 10, "h1");

        $I->see("Refunded", "main table tbody tr:nth-child(1) td:nth-child(4)");
    }

    /**
     * @group frontend
     * @group payment
     */
    public function ViewPaymentPage(AcceptanceTester $I)
    {
        // Navigate to the payment detail page
        $I->setPaymentStatus($this->paymentData['id'], 2);
        $I->amOnPage(sprintf(self::PAYMENT_VIEW_URL, $this->paymentData['id']));
        $I->waitForText("Payment #{$this->paymentData['id']}", 10);
        $log_created = date('Y-m-d H:i:s');

        // Capture a screenshot of the view
        $I->makeScreenshot("account-center-view-payment");

        // Verify payment details are displayed correctly
        $I->see("Amount: \$" . number_format($this->paymentData['amount'], 2));
        $I->see("Status: Completed");
        $I->see("Payment Date: {$this->paymentData['payment_date']}");
        $I->see($this->paymentData['payment_method']);

        // Verify related invoice information is shown
        $I->see("Invoices Paid With This Payment:");
        $I->see("Invoice #{$this->invoiceData['number']}", "ul.list-group li a");

        /*
        $I->seeInDatabase("jos_mothership_logs", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'user_id' => $this->joomlaUserData['id'],            
            'action' => 'viewed',
            'object_type' => 'payment',
            'object_id' => $this->accountData['id'],
            'created' => $log_created,
        ]);
        */


    }

}