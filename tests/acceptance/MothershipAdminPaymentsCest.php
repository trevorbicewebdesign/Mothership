<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;


class MothershipAdminPaymentsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $invoiceItemData = [];
    private $paymentData;

    const PAYMENTS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=payments";
    const PAYMENT_EDIT_URL = "/administrator/index.php?option=com_mothership&view=payment&layout=edit&id=%s";
    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $clientData2 = $I->createMothershipClient([
            'name' => 'Acme Inc.',
        ]);

        $accountData2 = $I->createMothershipAccount([
            'client_id' => $clientData2['id'],
            'name' => 'Roadrunner Products',
        ]);

        $this->userData = $I->createMothershipUser([
            'user_id' => '43',
            'client_id' => $this->clientData['id'],
        ]);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->invoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '175.00',
            'number' => 1000,
            'due_date' => NULL,
            'created' => date('Y-m-d H:i:s'),
            'status' => 1,
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
            'amount' => 103.2,
            'fee_amount' => 3.2,
            'fee_passed_on' => FALSE,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 2,
            'processed_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Navigate to the login page
        $I->amOnPage("/administrator/");

        // Log in with valid credentials
        $I->fillField("input[name=username]", "admin");
        $I->fillField("input[name=passwd]", "password123!test");
        $I->click("Log in");
        $I->waitForText("Hide Forever");
        $I->click("Hide Forever");
    }

    /**
     * @group backend
     * @group payment
     * @group client
     * @group backend-payment
     */
    public function MothershipCancelClientEdit(AcceptanceTester $I)
    {
        $I->amOnPage( self::PAYMENTS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");
        $I->click("Test Client");
        $I->wait(1);
        $I->waitForText("Mothership: Edit Client", 20, "h1.page-title");
        $I->click("Close", "#toolbar");
        $I->wait(1);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");
        $I->seeCurrentUrlEquals(self::PAYMENTS_VIEW_ALL_URL);
    }

    /**
     * @group backend
     * @group payment
     * @group account
     * @group backend-payment
     */
    public function MothershipCancelAccountEdit(AcceptanceTester $I)
    {
        $I->amOnPage( self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");

        $I->click("Test Account");
        $I->waitForText("Mothership: Edit Account", 20, "h1.page-title");
        $I->wait(1);
        $I->click("Close", "#toolbar");
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");
        $I->wait(1);
        $I->seeCurrentUrlEquals(self::PAYMENTS_VIEW_ALL_URL);
    }

    /**
     * @group backend
     * @group payment
     * @group invoice
     * @group backend-payment
     */
    public function MothershipCancelInvoiceEdit(AcceptanceTester $I)
    {
        $paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 103.2,
            'fee_amount' => 3.2,
            'fee_passed_on' => FALSE,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 2,
            'processed_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $invoicePaymentData = $I->createMothershipInvoicePayment([
            'invoice_id' => $this->invoiceData['id'],
            'payment_id' => $paymentData['id'],
            'applied_amount' => 103.20,
        ]);

        $I->amOnPage( self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");

        $I->click("Invoice #{$this->invoiceData['id']}");
        $I->waitForText("Mothership: Edit Invoice", 20, "h1.page-title");
        $I->click("Close", "#toolbar");
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");
        $I->seeCurrentUrlEquals(self::PAYMENTS_VIEW_ALL_URL);
    }

    /**
     * @group backend
     * @group payment
     * @group backend-payment
     */
    public function MothershipViewAllPayments(AcceptanceTester $I)
    {
        $paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 103.2,
            'fee_amount' => 3.2,
            'fee_passed_on' => FALSE,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 2,
            'processed_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $invoicePaymentData = $I->createMothershipInvoicePayment([
            'invoice_id' => $this->invoiceData['id'],
            'payment_id' => $paymentData['id'],
            'applied_amount' => 103.20,
        ]);

        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");

        $I->makeScreenshot("mothership-view-payments");

        $I->dontSee("Warning");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");
        
        $I->see("ID", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Payment Date", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("Amount", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Payment Method", "#j-main-container table thead tr th:nth-child(5)");
        $I->see("Client", "#j-main-container table thead tr th:nth-child(6)");
        $I->see("Account", "#j-main-container table thead tr th:nth-child(7)");
        $I->see("Created", "#j-main-container table thead tr th:nth-child(8)");
        $I->see("Status", "#j-main-container table thead tr th:nth-child(9)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $I->see("{$this->paymentData['id']}", "#j-main-container table tbody tr td:nth-child(2)");
        //$I->see("{$this->paymentData['payment_date']}", "#j-main-container table tbody tr td:nth-child(3)");
        $I->see("{$this->paymentData['amount']}", "#j-main-container table tbody tr td:nth-child(4)");
        $I->see("{$this->paymentData['payment_method']}", "#j-main-container table tbody tr td:nth-child(5)");
        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr td:nth-child(6)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr td:nth-child(7)");
        $I->see("Completed", "#j-main-container table tbody tr td:nth-child(9)");    

        $I->see("1 - 2 / 2 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group payment
     * @group delete
     * @group backend-payment
     */
    public function MothershipDeletePaymentSuccess(AcceptanceTester $I)
    {
        $invoicePaymentData = $I->createMothershipInvoicePayment([
            'invoice_id' => $this->invoiceData['id'],
            'payment_id' => $this->paymentData['id'],
        ]);

        $I->seeInDatabase('jos_mothership_payments', ['id' => $this->paymentData['id']]);
        $I->seeInDatabase('jos_mothership_invoice_payment', ['payment_id' => $this->paymentData['id']]);

        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");

        $I->seeElement(".btn-toolbar");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);
        $I->seeInDatabase('jos_mothership_payments', ['id' => $this->paymentData['id']]);
        $I->seeInDatabase('jos_mothership_invoice_payment', ['payment_id' => $this->paymentData['id']]);

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "payments.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "payment.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "payments.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::PAYMENTS_VIEW_ALL_URL);
        $I->see("Mothership: Payments", "h1.page-title");
        $I->see("1 Payment deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 0);

        $I->dontSeeInDatabase('jos_mothership_payments', ['id' => $this->paymentData['id']]);
        $I->dontSeeInDatabase('jos_mothership_invoice_payment', ['payment_id' => $this->paymentData['id']]);
    }

    /**
     * @group backend
     * @group payment
     * @group delete
     * @group backend-payment
     */
    public function MothershipDeletePaymentNoInvoicePayment(AcceptanceTester $I)
    {
        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");

        $I->seeElement(".btn-toolbar");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);
        $I->seeInDatabase('jos_mothership_payments', ['id' => $this->paymentData['id']]);

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "payments.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::PAYMENTS_VIEW_ALL_URL);
        $I->see("Mothership: Payments", "h1.page-title");
        $I->see("1 Payment deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 0);

        $I->dontSeeInDatabase('jos_mothership_payments', ['id' => $this->paymentData['id']]);
    }

    /**
     * @group backend
     * @group payment
     * @group delete
     * @group backend-payment
     */
    public function MothershipDeleteMultiplePayments(AcceptanceTester $I)
    {
        $payment1 = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 80.00,
            'payment_method' => 'paypal',
            'status' => 2,
        ]);

        $payment2 = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 100.00,
            'payment_method' => 'stripe',
            'status' => 2,
        ]);

        $I->amOnPage(MothershipAdminPaymentsCest::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->click("Delete");
        $I->wait(1);

        $I->see("3 Payments deleted successfully.", ".alert-message");
        $I->dontSeeInDatabase('jos_mothership_payments', ['id' => $payment1['id']]);
        $I->dontSeeInDatabase('jos_mothership_payments', ['id' => $payment2['id']]);
    }

    /**
     * @group backend
     * @group payment
     * @group backend-payment
     */
    public function MothershipAddPayment(AcceptanceTester $I)
    {
        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");
        $I->click("{$toolbar} {$toolbarNew} .btn.button-new");
        $I->waitForText("Mothership: New Payment", 20, "h1.page-title");

        $I->makeScreenshot("mothership-add-payment");

        $I->dontSee("Warning");

        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        $I->seeElement("select#jform_client_id");
        $I->seeElement("select#jform_account_id");
        $I->seeElement("input#jform_amount");
        $I->seeElement("input#jform_fee_amount");
        $I->seeElement("#jform_fee_passed_on");
        $I->seeElement("input#jform_payment_date");
        $I->seeElement("input#jform_transaction_id");
        $I->seeElement("#jform_status");

        // Attempt to save the form without filling out any fields
        $I->click("Save", "#toolbar");
        $I->wait(1);

        // Check the form validation
        $I->see("The form cannot be submitted as it's missing required data.");
        $I->see("Please correct the marked fields and try again.");
        
        $I->see("One of the options must be selected", "label#jform_client_id-lbl .form-control-feedback");
        $I->see("One of the options must be selected", "label#jform_account_id-lbl .form-control-feedback");
        
        $I->amGoingTo("Fill out the form");

        $I->selectOption("select#jform_client_id", $this->clientData['id']);
        $I->waitForElementVisible("select#jform_account_id", 5);
        $I->selectOption("select#jform_account_id", $this->accountData['id']);
        
        $I->fillFIeld("input#jform_amount", "103.20");
        $I->fillFIeld("input#jform_fee_amount", "3.20");
        $I->fillFIeld("input#jform_payment_method", "PayPal");
        $I->fillFIeld("input#jform_transaction_id", "123456");

        $I->click("Save & Close", "#toolbar");
        $I->waitForText("Payment saved successfully.", 5, "#system-message-container .alert-message");
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $I->see("103.20", "#j-main-container table.itemList tbody tr td:nth-child(4)");
        $I->see("PayPal", "#j-main-container table.itemList tbody tr td:nth-child(5)");
        $I->see("Test Client", "#j-main-container table.itemList tbody tr td:nth-child(6)");
        $I->see("Test Account", "#j-main-container table.itemList tbody tr td:nth-child(7)");
        $I->see(date("Y-m-d"), "#j-main-container table.itemList tbody tr td:nth-child(8)");

        // Open the Invoice again and confirm the data is correct
        $I->amOnPage(sprintf(self::PAYMENT_EDIT_URL, ($this->paymentData['id'] + 1)));
        // Confirm the value in jform_number is correct
        $I->seeInField("input#jform_amount", "103.20");

        $I->click("Save", "#toolbar");
        $I->wait(1);

        // We should still be on the same edit page, with the same ID
        $I->seeInCurrentUrl(sprintf(self::PAYMENT_EDIT_URL, ($this->paymentData['id'] + 1)));
        $I->waitForText("Payment saved successfully.", 5, "#system-message-container .alert-message");

        // Check that the invoice displays the same data that was entered before
        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");
        $I->seeOptionIsSelected("select#jform_account_id", "Test Account");
    }

    /**
     * @group backend
     * @group invoice
     * @group backend-payment
     */
    public function LockedPaymentCannotBeEdited(AcceptanceTester $I)
    {
        $paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 103.2,
            'fee_amount' => 3.2,
            'fee_passed_on' => FALSE,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 1,
            'processed_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Set the invoice to the locked state
        $I->setPaymentLocked($paymentData['id']);
        $I->amOnPage(sprintf(self::PAYMENT_EDIT_URL, $paymentData['id']));
        $I->waitForText("Mothership: View Payment", 20, "h1.page-title");

        $I->seeElement("#jform_client_id");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_client_id", "disabled"));
        $I->seeElement("#jform_account_id");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_account_id", "disabled"));

        $I->click("Unlock", "#toolbar");
        $I->waitForText("Mothership: Edit Payment", 20, "h1.page-title");
        $I->waitForText("Payment unlocked successfully.", 20, "#system-message-container .alert-message");

        $I->seeElement("#jform_client_id");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_client_id", "disabled"));
        $I->seeElement("#jform_account_id");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_account_id", "disabled"));

        $I->click("Lock", "#toolbar");
        $I->waitForText("Mothership: View Payment", 20, "h1.page-title");
        $I->waitForText("Payment locked successfully.", 20, "#system-message-container .alert-message");
    }

    /**
     * @group backend
     * @group payment
     * @group delete
     * @group backend-payment
     */
    public function MothershipDeletePayment(AcceptanceTester $I)
    {
        $clientData = $I->createMothershipClient([
            'name' => 'Test Client 2',
        ]);
        $accountData = $I->createMothershipAccount([
            'client_id' => $clientData['id'],
            'name' => 'Test Account 2',
        ]);

        $paymentData = $I->createMothershipPayment([
            'client_id' => $clientData['id'],
            'account_id' => $accountData['id'],
            'amount' => 103.2,
            'fee_amount' => 3.2,
            'fee_passed_on' => FALSE,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 1,
            'processed_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $I->seeInDatabase("jos_mothership_payments", [ 'id' => $paymentData['id'] ]);
        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "payments.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "payment.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "payments.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::PAYMENTS_VIEW_ALL_URL);
        $I->see("Mothership: Payments", "h1.page-title");
        $I->see("2 Payments deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 0);

        $I->dontSeeInDatabase("jos_mothership_payments", [ 'id' => $paymentData['id'] ]);
    }

    /**
     * @group backend
     * @group payment
     * @group backend-payment
     */
    public function ManuallyConfirmPendingPayment(AcceptanceTester $I)
    {
        $paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 103.2,
            'fee_amount' => 3.2,
            'fee_passed_on' => FALSE,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 1,
            'processed_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $I->seeInDatabase("jos_mothership_payments", [ 
            'id' => $paymentData['id'], 
            'status' => 1 
        ]);

        $I->amOnPage(sprintf(self::PAYMENT_EDIT_URL, $paymentData['id']));
        $I->wait(1);
        $I->waitForText("Mothership: Edit Payment", 20, "h1.page-title");

        $I->see("Confirm Payment", "#toolbar");
        $I->seeElement("joomla-toolbar-button#toolbar-vcard", ['task' => "payment.confirm"]);

        $I->click("Confirm Payment", "#toolbar");
        $I->wait(1);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");
        $I->see("Payment {$paymentData['id']} confirmed successfully.", ".alert-message");

        $I->seeInDatabase("jos_mothership_payments", [ 
            'id' => $paymentData['id'], 
            'status' => 2 
        ]);

        $I->see("Completed", "#j-main-container table tbody tr:nth-child(2) td:nth-child(9)");
        $I->dontSee("Confirm", "#j-main-container table tbody tr:nth-child(2) td:nth-child(9) button");
        $I->dontSee("Pending", "#j-main-container table tbody tr:nth-child(2) td:nth-child(9)");

        $I->setPaymentStatus($paymentData['id'], 1);
        $I->seeInDatabase("jos_mothership_payments", [ 
            'id' => $paymentData['id'], 
            'status' => 1 
        ]);

        $I->amOnPage(self::PAYMENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);
        $I->see("Pending", "#j-main-container table tbody tr:nth-child(2) td:nth-child(9)");
        $I->see("Confirm", "#j-main-container table tbody tr:nth-child(2) td:nth-child(9)");

        $I->click("Confirm", "#j-main-container table tbody tr:nth-child(2) td:nth-child(9)");
        $I->wait(1);
        $I->waitForText("Mothership: Payments", 20, "h1.page-title");
        $I->see("Payment {$paymentData['id']} confirmed successfully.", ".alert-message");
        $I->seeInDatabase("jos_mothership_payments", [ 
            'id' => $paymentData['id'], 
            'status' => 2 
        ]);

        $I->seeInDatabase("jos_mothership_logs", [ 
            'client_id' => $this->clientData['id'], 
            'account_id' => $this->accountData['id'],
            'action' => 'payment_status_changed', 
            'object_id' => $paymentData['id'], 
            'object_type' => 'payment', 
            'user_id' => 1, 
        ]);

    }
}