<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;


class MothershipAdminInvoicesCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $projectData;
    private $invoiceItemData = [];
    private $mothershipConfig = [];

    const INVOICES_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=invoices";
    const INVOICE_EDIT_URL = "/administrator/index.php?option=com_mothership&view=invoice&layout=edit&id=%s";
    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->mothershipConfig = $I->setMothershipConfig([
            'company_name' => 'A Fake Company',
            'company_address_1' => '12345 Nowhere St.',
            'company_city' => 'Nowhere',
            'company_state' => 'California',
            'company_zip' => '99999',
            'company_phone' => '555-555-5555',
            'company_default_rate' => '100.00',
        ]);

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
            'default_rate' => '111.00',
        ]);

        $this->userData = $I->createMothershipUser([
            'user_id' => '43',
            'client_id' => $this->clientData['id'],
        ]);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->projectData = $I->createMothershipProject([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'Test Project',
        ]);

        $clientData2 = $I->createMothershipClient([
            'name' => 'Acme Inc.',
        ]);

        $accountData2 = $I->createMothershipAccount([
            'client_id' => $clientData2['id'],
            'name' => 'Roadrunner Products',
        ]);

        $this->invoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => $this->projectData['id'],   
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
     * @group client
     * @group backend-invoice
     */
    public function MothershipCancelClientEdit(AcceptanceTester $I)
    {
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 10, "h1.page-title");

        $I->click("Test Client");
        $I->waitForText("Mothership: Edit Client", 10, "h1.page-title");
        $I->click("Close", "#toolbar");
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");
    }

    /**
     * @group backend
     * @group account
     * @group invoice
     * @group backend-invoice
     */
    public function MothershipCancelAccountEdit(AcceptanceTester $I)
    {
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        $I->click("Test Account");
        $I->wait(1);
         $I->waitForText("Mothership: Edit Account", 10, "h1.page-title");
        $I->click("Close", "#toolbar");
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");
    }

    /**
     * @group backend
     * @group payment
     * @group invoice
     * @group backend-invoice
     */
    public function MothershipCancelPaymentEdit(AcceptanceTester $I)
    {
        $paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => $this->invoiceData['total'],
            'payment_method' => 'paypal',
            'status' => 2,
        ]);
        $invoicePaymentData = $I->createMothershipInvoicePayment([
            'invoice_id' => $this->invoiceData['id'],
            'payment_id' => $paymentData['id'],
            'applied_amount' => $this->invoiceData['total'],
        ]);

        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        $I->click("Payment #{$paymentData['id']}");
        $I->waitForText("Mothership: Edit Payment", 10, "h1.page-title");
        $I->click("Close", "#toolbar");
        $I->wait(1);
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->see("Mothership: Invoices", "h1.page-title");
    }

    /**
     * @group backend
     * @group invoice
     * @group backend-invoice
     */
    public function MothershipViewInvoices(AcceptanceTester $I)
    {

        $invoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => NULL,
            'total' => '475.00',
            'number' => 1004,
            'created' => date('Y-m-d H:i:s'),
            'status' => 4,
            'locked' => 1,
        ]);

        $invoiceItemData[] = $I->createMothershipInvoiceItem([
            'invoice_id' => $invoiceData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'hours' => '1',
            'minutes' => '30',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);

        $invoiceItemData[] = $I->createMothershipInvoiceItem([
            'invoice_id' => $invoiceData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'hours' => '1',
            'minutes' => '0',
            'quantity' => '1',
            'rate' => '70.00',
            'subtotal' => '70.00',
        ]);
        
        $paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => $invoiceData['total'],
            'payment_method' => 'paypal',
            'status' => 2,
            'locked' => 1,
        ]);

        $paymentInvoiceData = $I->createMothershipInvoicePayment([
            'invoice_id' => $invoiceData['id'],
            'payment_id' => $paymentData['id'],
            'applied_amount' => $invoiceData['total'],
        ]);

        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        $I->makeScreenshot("mothership-view-invoices");

        $I->dontSee("Warning");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");
        
        $I->see("ID", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Invoice Number", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("PDF", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Client", "#j-main-container table thead tr th:nth-child(5)");
        $I->see("Account", "#j-main-container table thead tr th:nth-child(6)");
        $I->see("Project", "#j-main-container table thead tr th:nth-child(7)");
        $I->see("Total", "#j-main-container table thead tr th:nth-child(8)");
        $I->see("Status", "#j-main-container table thead tr th:nth-child(9)");
        $I->see("Payment Status", "#j-main-container table thead tr th:nth-child(10)");
        $I->see("Due", "#j-main-container table thead tr th:nth-child(11)");
        $I->see("Created", "#j-main-container table thead tr th:nth-child(12)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $row = 1;
        // This invoice is not locked, so it should not have the lock icon
        $I->dontSeeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(1) i.fa-solid.fa-lock");
        $I->see("{$this->invoiceData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(2)");
        $I->see("{$this->invoiceData['number']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->seeNumberOfElements("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a", 2);
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf");
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf");

        $downloadPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf", 'href');
        $previewPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf", 'href');

        $I->assertEquals("/administrator/index.php?option=com_mothership&task=invoice.downloadPdf&id={$this->invoiceData['id']}", $downloadPdfUrl);
        $I->assertEquals("/administrator/index.php?option=com_mothership&task=invoice.previewPdf&id={$this->invoiceData['id']}", $previewPdfUrl);

        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(6)");
        $I->see("{$this->projectData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(7)");
        $I->see("{$this->invoiceData['total']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(8)");
        $I->see("Draft", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(9)");
        $I->see("Unpaid", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(10)");
        $I->see(date('Y-m-d'), "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(12)");

        $row = 2;
        // This invoice IS not locked, so it SHOULD have the lock icon
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(1) i.fa-solid.fa-lock");
        $I->see("{$invoiceData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(2)");
        $I->see("{$invoiceData['number']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->seeNumberOfElements("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a", 2);
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf");
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf");

        $downloadPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf", 'href');
        $previewPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf", 'href');

        $I->assertEquals("/administrator/index.php?option=com_mothership&task=invoice.downloadPdf&id={$invoiceData['id']}", $downloadPdfUrl);
        $I->assertEquals("/administrator/index.php?option=com_mothership&task=invoice.previewPdf&id={$invoiceData['id']}", $previewPdfUrl);

        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(6)");
        $I->see("{$invoiceData['total']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(8)");
        $I->see("Closed", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(9)");
        $I->see("Paid", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(10)");
        $I->see("Payment #{$paymentData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(10)"); 
        $I->see(date('Y-m-d'), "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(12)");

        $I->see("1 - 2 / 2 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group invoice
     * @group delete
     * @group backend-invoice
     */
    public function MothershipDeleteInvoiceSuccess(AcceptanceTester $I)
    {
        $I->setInvoiceStatus($this->invoiceData['id'], 1);
        $I->seeInDatabase('jos_mothership_invoices', ['id' => $this->invoiceData['id']]);

        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "invoices.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "invoice.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "invoices.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::INVOICES_VIEW_ALL_URL);
        $I->see("Mothership: Invoices", "h1.page-title");
        $I->see("1 Invoice deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 0);

        $I->dontSeeInDatabase('jos_mothership_invoices', ['id' => $this->invoiceData['id']]);
    }

    /**
     * @group backend
     * @group invoice
     * @group delete
     * @group backend-invoice
     */
    public function MothershipPreventDeleteClosedInvoice(AcceptanceTester $I)
    {
        // Create draft invoice (deletable)
        $draftInvoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '175.00',
            'number' => 1000,
            'created' => date('Y-m-d H:i:s'),
            'status' => 1, // Draft
        ]);

        // Create closed invoice (not deletable)
        $closedInvoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '99.99',
            'number' => 1002,
            'created' => date('Y-m-d H:i:s'),
            'status' => 4, // Closed
        ]);

        // Payment and link for draft invoice
        $paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => $draftInvoiceData['total'],
            'payment_method' => 'paypal',
        ]);

        $invoicePaymentData = $I->createMothershipInvoicePayment([
            'invoice_id' => $draftInvoiceData['id'],
            'payment_id' => $paymentData['id'],
            'applied_amount' => $draftInvoiceData['total'],
        ]);

        // Payment and link for closed invoice
        $closedPaymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => $closedInvoiceData['total'],
            'payment_method' => 'manual',
        ]);

        $closedInvoicePaymentData = $I->createMothershipInvoicePayment([
            'invoice_id' => $closedInvoiceData['id'],
            'payment_id' => $closedPaymentData['id'],
            'applied_amount' => $closedInvoiceData['total'],
        ]);

        // Confirm both invoices are in the DB
        $I->seeInDatabase('jos_mothership_invoices', ['id' => $this->invoiceData['id'], 'status' => 1]);
        $I->seeInDatabase('jos_mothership_invoices', ['id' => $draftInvoiceData['id'], 'status' => 1]);
        $I->seeInDatabase('jos_mothership_invoices', ['id' => $closedInvoiceData['id'], 'status' => 4]);

        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        // Select both invoices
        $I->click("input[name=checkall-toggle]");
        $I->wait(1);
        $I->click("Actions");
        $I->click("Delete");
        $I->wait(1);

        // Expect one to be deleted and one skipped
        $I->see("2 Invoices deleted successfully.", ".alert-message");
        $I->see("Invoice {$closedInvoiceData['id']} delete skipped. Only draft invoices can be deleted.", ".alert-message");

        // Confirm only the closed one remains
        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 1);
        $I->see("{$closedInvoiceData['number']}", "#j-main-container table.itemList tbody tr td:nth-child(3)");
        $I->see("Closed", "#j-main-container table.itemList tbody tr td:nth-child(8)");

        // Database cleanup checks
        $I->dontSeeInDatabase('jos_mothership_invoices', ['id' => $draftInvoiceData['id']]);
        $I->dontSeeInDatabase('jos_mothership_invoice_payment', ['id' => $invoicePaymentData['id']]);

        $I->seeInDatabase('jos_mothership_invoices', ['id' => $closedInvoiceData['id']]);
        $I->seeInDatabase('jos_mothership_invoice_payment', ['id' => $closedInvoicePaymentData['id']]);

        $I->dontSeeInDatabase('jos_mothership_invoices', ['id' => $this->invoiceData['id']]);
        $I->dontSeeInDatabase('jos_mothership_invoice_items', ['id' => $this->invoiceItemData[0]['id']]);
        $I->dontSeeInDatabase('jos_mothership_invoice_items', ['id' => $this->invoiceItemData[1]['id']]);
        $I->dontSeeInDatabase('jos_mothership_invoice_payment', ['id' => $this->invoiceData['id']]);
    }


    protected function invoiceStatusProvider(): array
    {
        return [
            ['status' => 2, 'label' => 'Opened'],
            ['status' => 3, 'label' => 'Canceled'],
            ['status' => 4, 'label' => 'Closed'],
        ];
    }

    /**
     * @group backend
     * @group invoice
     * @group delete
     * @group backend-invoice
     * @dataProvider invoiceStatusProvider
     */
    public function MothershipCannotDeleteNonDraftInvoices(AcceptanceTester $I, \Codeception\Example $example)
    {
        $status = $example['status'];
        $label  = $example['label'];

        $invoice = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'number' => 9000 + $status,
            'status' => $status,
            'total' => '123.45',
        ]);

        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        // Select the invoice row
        $I->see((string) $invoice['number'], "#j-main-container table.itemList tbody tr td:nth-child(3)");
        $I->click("input[name=checkall-toggle]");

        $I->click("Actions");
        $I->click("Delete");

        $I->wait(1);
        $I->seeInCurrentUrl(self::INVOICES_VIEW_ALL_URL);

        $I->see("Invoice {$invoice['id']} delete skipped. Only draft invoices can be deleted.", ".alert-message");

        $I->seeInDatabase("jos_mothership_invoices", [
            'id' => $invoice['id'],
            'status' => $status,
        ]);
    }

    /**
     * @group backend
     * @group invoice
     * @group backend-invoice
     */
    public function MothershipAddInvoice(AcceptanceTester $I)
    {
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");
        $I->click("{$toolbar} {$toolbarNew} .btn.button-new");
        $I->waitForText("Mothership: New Invoice", 20, "h1.page-title");
        $I->wait(5);

        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        $I->seeElement("select#jform_client_id");
        $I->dontSeeElement("select#jform_account_id");
        $I->dontSeeElement("select#jform_project_id");
        $I->seeElement("input#jform_number");
        $I->seeElement("input#jform_created");
        $I->seeElement("input#jform_due_date");
        $I->seeElement("input#jform_rate");
        $I->seeElement("input#jform_total");

        // Attempt to save the form without filling out any fields
        $I->click("Save", "#toolbar");
        $I->wait(5);

        // Check the form validation
        $I->see("The form cannot be submitted as it's missing required data.");
        $I->see("Please correct the marked fields and try again.");
        
        $I->see("One of the options must be selected", "label#jform_client_id-lbl .form-control-feedback");
        $I->see("Please fill in this field", "label#jform_number-lbl .form-control-feedback");
        $I->see("Please provide an item name.", ".form-group .invalid-feedback");

        $I->amGoingTo("Fill out the form");

        $I->selectOption("select#jform_client_id", $this->clientData['id']);
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_client_id", "{$this->clientData['name']}");
        $I->selectOption("select#jform_account_id", $this->accountData['id']);
        $I->dontSeeElement("select#jform_project_id");
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_account_id", "{$this->accountData['name']}");
        $I->wait(5);
        $I->selectOption("select#jform_project_id", "{$this->projectData['id']}");
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_project_id", "{$this->projectData['name']}");

        $I->fillFIeld("input#jform_number", "1001");
        $I->seeInField("input#jform_rate", $this->clientData['default_rate']);

        $I->fillFIeld("input#jform_total", "105.00");

        $I->amGoingTo("Fill out the first row of the invoice items table");
        $I->fillField("#invoice-items-table input[name='jform[items][0][name]']", "Test Item");
        $I->fillField("#invoice-items-table input[name='jform[items][0][description]']", "Test Description");

        $I->fillField("#invoice-items-table input[name='jform[items][0][hours]']", "1");

        $I->seeInField("#invoice-items-table input[name='jform[items][0][hours]']", "1");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][minutes]']", "0");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][quantity]']", "1.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][rate]']", $this->clientData['default_rate']);
        $expectedSubtotal = number_format(($this->clientData['default_rate'] * 1), 2); // Update this value if needed based on calculations
        $I->seeInField("#invoice-items-table input[name='jform[items][0][subtotal]']", $expectedSubtotal);

        $I->fillField("#invoice-items-table input[name='jform[items][0][minutes]']", "30");

        $I->seeInField("#invoice-items-table input[name='jform[items][0][hours]']", "1");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][minutes]']", "30");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][quantity]']", "1.50");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][rate]']", $this->clientData['default_rate']);
        $expectedSubtotal = number_format(($this->clientData['default_rate'] * 1.5), 2); // Update this value if needed based on calculations
        $I->seeInField("#invoice-items-table input[name='jform[items][0][subtotal]']", $expectedSubtotal);

        // Delete whats in quantity
        $I->executeJS("document.querySelector(\"#invoice-items-table input[name='jform[items][0][quantity]']\").value = '';");
        $I->fillField("#invoice-items-table input[name='jform[items][0][quantity]']", "2.00");

        $I->seeInField("#invoice-items-table input[name='jform[items][0][hours]']", "2");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][minutes]']", "0");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][quantity]']", "2.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][rate]']", $this->clientData['default_rate']);
        $expectedSubtotal = number_format(($this->clientData['default_rate'] * 2), 2); // Update this value if needed based on calculations
        $I->seeInField("#invoice-items-table input[name='jform[items][0][subtotal]']", $expectedSubtotal);

        $I->executeJS("document.querySelector(\"#invoice-items-table input[name='jform[items][0][rate]']\").value = '';");
        $I->fillField("table tbody tr:first-child input[name='jform[items][0][rate]']", "70.00");

        $I->click("#invoice-items-table input[name='jform[items][0][subtotal]']");

        $I->seeInField("#invoice-items-table input[name='jform[items][0][hours]']", "2");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][minutes]']", "0");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][quantity]']", "2.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][rate]']", "70.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][subtotal]']", "140.00");

        $I->seeInField("input#jform_total", "140.00");

        $I->executeJS("document.querySelector('#add-invoice-item').scrollIntoView({ behavior: 'instant', block: 'center' });");
        $I->wait(1);
        $I->click("#add-invoice-item");

        $I->dontSee("#invoice-items-table input[name='jform[items][2][name]']");

        $I->fillField("#invoice-items-table input[name='jform[items][1][name]']", "A different Item");
        $I->fillField("#invoice-items-table input[name='jform[items][1][description]']", "Test Description");

        $I->fillField("#invoice-items-table input[name='jform[items][1][hours]']", "2");

        $I->seeInField("#invoice-items-table input[name='jform[items][1][hours]']", "2");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][minutes]']", "0");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][quantity]']", "2.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][rate]']", "0.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][subtotal]']", "0.00");

        $I->fillField("#invoice-items-table input[name='jform[items][1][minutes]']", "45");

        $I->seeInField("#invoice-items-table input[name='jform[items][1][hours]']", "2");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][minutes]']", "45");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][quantity]']", "2.75");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][rate]']", "0.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][subtotal]']", "0.00");

        $I->executeJS("document.querySelector(\"#invoice-items-table input[name='jform[items][1][quantity]']\").value = '';");
        $I->fillField("#invoice-items-table input[name='jform[items][1][quantity]']", "3.75");

        $I->seeInField("#invoice-items-table input[name='jform[items][1][hours]']", "3");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][minutes]']", "45");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][quantity]']", "3.75");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][rate]']", "0.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][subtotal]']", "0.00");

        $I->executeJS("document.querySelector(\"#invoice-items-table input[name='jform[items][1][rate]']\").value = '';");
        $I->fillField("#invoice-items-table input[name='jform[items][1][rate]']", "70.00");
        $I->click("#invoice-items-table input[name='jform[items][1][subtotal]']");

        $I->seeInField("#invoice-items-table input[name='jform[items][1][hours]']", "3");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][minutes]']", "45");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][quantity]']", "3.75");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][rate]']", "70.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][subtotal]']", "262.50");

        $I->fillField("input#jform_total", "402.50");

        $I->click("Save & Close", "#toolbar");
        $I->waitForText("Invoice saved successfully.", 5, "#system-message-container .alert-message");

        // Check that the new invoice has two rows of items
        $I->assertInvoiceHasRows(($this->invoiceData['id'] + 1), 2);
        $I->assertInvoiceHasItems($this->invoiceData['id'] + 1, [
            ['name' => 'Test Item', 'description' => 'Test Description', 'hours' => 2, 'minutes' => 0, 'quantity' => 2.00, 'rate' => 70.0, 'subtotal' => 140.00],
            ['name' => 'A different Item', 'description' => 'Test Description', 'hours' => 3, 'minutes' => 45, 'quantity' => 3.75, 'rate' => 70.0, 'subtotal' => 262.50],
        ]);

        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $I->see("1001", "#j-main-container table.itemList tbody tr td:nth-child(3)");
        $I->see("Test Client", "#j-main-container table.itemList tbody tr td:nth-child(5)");
        $I->see("Test Account", "#j-main-container table.itemList tbody tr td:nth-child(6)");
        $I->see(date("Y-m-d"), "#j-main-container table.itemList tbody tr td:nth-child(11)");

        // Open the Invoice again and confirm the data is correct
        $I->amOnPage(sprintf(self::INVOICE_EDIT_URL, ($this->invoiceData['id'] + 1)));
        // Confirm the value in jform_number is correct
        $I->seeInField("input#jform_number", "1001");

        $I->click("Save", "#toolbar");
        $I->wait(2);

        // We should still be on the same edit page, with the same ID
        $I->seeInCurrentUrl(sprintf(self::INVOICE_EDIT_URL, ($this->invoiceData['id'] + 1)));
        $I->see("Invoice saved successfully.", "#system-message-container .alert-message");

        // Check that the invoice displays the same data that was entered before
        $I->seeInField("input#jform_created", date('Y-m-d'));

        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");

        $I->seeInField("#invoice-items-table input[name='jform[items][0][name]']", "Test Item");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][description]']", "Test Description");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][hours]']", "2");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][minutes]']", "0");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][quantity]']", "2");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][rate]']", "70.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][0][subtotal]']", "140.00");
        // Now check the second row of items
        $I->seeInField("#invoice-items-table input[name='jform[items][1][name]']", "A different Item");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][description]']", "Test Description");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][hours]']", "3");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][minutes]']", "45");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][quantity]']", "3.75");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][rate]']", "70.00");
        $I->seeInField("#invoice-items-table input[name='jform[items][1][subtotal]']", "262.50");

    }

    /**
     * @group backend
     * @group invoice
     * @group backend-invoice
     */
    public function SetInvoiceOpened(AcceptanceTester $I)
    {
        $I->seeInDatabase("jos_mothership_invoices", [
            'id' => $this->invoiceData['id'],
            'locked' => 0,
            'status' => 1,
        ]);   
        $I->amOnPage(sprintf(self::INVOICE_EDIT_URL, $this->invoiceData['id']));
        $I->waitForText("Mothership: Edit Invoice", 20, "h1.page-title");

        $I->seeOptionIsSelected("select#jform_status", "Draft");
        $I->selectOption("select#jform_status", "Opened");
        $I->fillFIeld("input#jform_rate", "70.00");
        $I->click("Save", "#toolbar"); 
        $I->waitForText("Invoice saved successfully.", 5, "#system-message-container .alert-message");
        $I->waitForText("Mothership: View Invoice", 20, "h1.page-title");
        
        $I->seeInDatabase("jos_mothership_invoices", [
            'id' => $this->invoiceData['id'],
            'locked' => 1,
            'status' => 2,
        ]);   

        $I->seeInDatabase('jos_mothership_logs', [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'action' => 'status_opened',
            'object_id' => $this->invoiceData['id'],
            'object_type' => 'invoice',
        ]);

        $email_id = $I->getLastEmailId(); 
        codecept_debug($email_id);
        $I->assertEmailSubjectEquals($email_id, "New Invoice Opened");
    }

    /**
     * @group backend
     * @group invoice
     * @group backend-invoice
     */
    public function LockedInvoiceCannotBeEdited(AcceptanceTester $I)
    {
        $I->seeInDatabase("jos_mothership_invoices", [
            'id' => $this->invoiceData['id'],
            'status' => 1,
        ]); 
        // Set the invoice to the locked state
        $I->setInvoiceLocked($this->invoiceData['id']);
        $I->amOnPage(sprintf(self::INVOICE_EDIT_URL, $this->invoiceData['id']));
        $I->waitForText("Mothership: View Invoice", 20, "h1.page-title");

        $I->seeElement("#jform_client_id");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_client_id", "disabled"));
        $I->seeElement("#jform_account_id");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_account_id", "disabled"));
        $I->seeElement("#jform_number");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_number", "disabled"));
        $I->seeElement("#jform_created");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_created", "disabled"));
        $I->seeElement("#jform_due_date");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_due_date", "disabled"));
        $I->seeElement("#jform_rate");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_rate", "disabled"));
        $I->seeElement("#jform_total");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_total", "disabled"));
        $I->seeElement("#jform_status");
        $I->assertEquals("true", $I->grabAttributeFrom("#jform_status", "disabled"));

        $I->seeElement("#invoice-items-table input[name='jform[items][0][name]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][name]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][description]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][description]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][hours]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][hours]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][minutes]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][minutes]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][quantity]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][quantity]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][rate]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][rate]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][subtotal]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][subtotal]']", "disabled"));

        $I->seeElement("#invoice-items-table input[name='jform[items][1][name]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][name]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][description]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][description]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][hours]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][hours]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][minutes]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][minutes]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][quantity]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][quantity]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][rate]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][rate]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][subtotal]']");
        $I->assertEquals("true", $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][subtotal]']", "disabled"));

        $I->click("Unlock", "#toolbar");
        $I->waitForText("Mothership: Edit Invoice", 20, "h1.page-title");
        $I->waitForText("Invoice unlocked successfully.", 20, "#system-message-container .alert-message");

        $I->seeElement("#jform_client_id");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_client_id", "disabled"));
        $I->seeElement("#jform_account_id");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_account_id", "disabled"));
        $I->seeElement("#jform_number");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_number", "disabled"));
        $I->seeElement("#jform_created");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_created", "disabled"));
        $I->seeElement("#jform_due_date");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_due_date", "disabled"));
        $I->seeElement("#jform_rate");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_rate", "disabled"));
        $I->seeElement("#jform_total");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_total", "disabled"));
        $I->seeElement("#jform_status");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#jform_status", "disabled"));

        $I->seeElement("#invoice-items-table input[name='jform[items][0][name]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][name]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][description]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][description]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][hours]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][hours]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][minutes]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][minutes]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][quantity]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][quantity]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][rate]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][rate]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][0][subtotal]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][0][subtotal]']", "disabled"));

        $I->seeElement("#invoice-items-table input[name='jform[items][1][name]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][name]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][description]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][description]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][hours]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][hours]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][minutes]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][minutes]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][quantity]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][quantity]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][rate]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][rate]']", "disabled"));
        $I->seeElement("#invoice-items-table input[name='jform[items][1][subtotal]']");
        $I->assertEquals(NULL, $I->grabAttributeFrom("#invoice-items-table input[name='jform[items][1][subtotal]']", "disabled"));

        $I->click("Lock", "#toolbar");
        $I->waitForText("Mothership: View Invoice", 20, "h1.page-title");
        $I->waitForText("Invoice locked successfully.", 20, "#system-message-container .alert-message");
    }

    

    /**
     * @group backend
     * @group invoice
     * @group backend-invoice
     */
    public function invoiceViewPdf(AcceptanceTester $I)
    {
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        $I->seeElement("#j-main-container table.itemList tbody tr:first-child a.downloadPdf");

        // I want to grab the html from the 4th child td element which has an a tag in it
        $html = $I->grabAttributeFrom("#j-main-container table.itemList tbody tr:first-child a.downloadPdf", 'href');
        codecept_debug($html);
        // Click on the 4th child td element which has an a tag in it
        $I->click("#j-main-container table.itemList tbody tr:first-child a.downloadPdf");
        $I->amOnPage($html);
        $I->wait(1);
        //$I->seeElement("embed[type='application/pdf']");
    }

    /**
     * @group backend
     * @group invoice
     * @group backend-invoice
     */
    public function invoiceViewPdfTemplate(AcceptanceTester $I)
    {
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Invoices", 20, "h1.page-title");

        $I->seeElement("#j-main-container table.itemList tbody tr:first-child a.previewPdf");

        // I want to grab the html from the 4th child td element which has an a tag in it
        $html = $I->grabAttributeFrom("#j-main-container table.itemList tbody tr:first-child a.previewPdf", 'href');
        codecept_debug($html);
        // Click on the 4th child td element which has an a tag in it
        $I->click("#j-main-container table.itemList tbody tr:first-child a.previewPdf");
        $I->amOnPage($html);
        $I->wait(1);
        // take a screen shot
        $I->see("Invoice #{$this->invoiceData['number']}");
    }

}