<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipFrontInvoicesCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $mothershipConfig;
    private $joomlaUserData;
    private $invoiceItemData = [];

    const INVOICES_VIEW_ALL_URL = "index.php?option=com_mothership&view=invoices";
    const INVOICES_VIEW_ALL_SEF_URL = "/account-center/billing/invoices/";

    const INVOICE_VIEW_URL = "index.php?option=com_mothership&view=invoice&layout=default&id=%s";
    const INVOICE_VIEW_SEF_URL = "/account-center/billing/invoices/%s/";

    const INVOICE_VIEW_PDF_URL = "index.php?option=com_mothership&view=invoice&controller=invoice&id=%s&task=viewPdf";
    const INVOICE_VIEW_PDF_SEF_URL = "/account-center/billing/invoices/%s/viewPdf/";

    const INVOICE_PAY_URL = "index.php?option=com_mothership&task=invoice.payment&id=%s";
    const INVOICE_PAY_SEF_URL = "/account-center/billing/invoices/%s/pay/";

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
     * @group invoice
     */
    public function ViewAllInvoicesPage(AcceptanceTester $I)
    {
        // Verify redirection to account center
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        $I->makeScreenshot("account-center-view-all-invoices");

        // Confirm the correct number of records
        $I->seeNumberOfElements("table#invoicesTable tbody tr", 1);

        $I->see("Invoice Status Legend", ".mt-4 .col-md-6:nth-child(1)");
        $I->see("Opened", ".mt-4 .col-md-6:nth-child(1) ul.mb-0 li:nth-child(1)");
        $I->see("Cancelled", ".mt-4 .col-md-6:nth-child(1) ul.mb-0 li:nth-child(2)");
        $I->see("Closed", ".mt-4 .col-md-6:nth-child(1) ul.mb-0 li:nth-child(3)");
        $I->seeNumberOfElements(".mt-4 .col-md-6:nth-child(1) ul.mb-0 li", 3);

        $I->see("Payment Status Legend", ".mt-4 .col-md-6:nth-child(2)");
        $I->see("Unpaid", ".mt-4 .col-md-6:nth-child(2) ul.mb-0 li:nth-child(1)");
        $I->see("Paid", ".mt-4 .col-md-6:nth-child(2) ul.mb-0 li:nth-child(2)");
        $I->see("Partially Paid", ".mt-4 .col-md-6:nth-child(2) ul.mb-0 li:nth-child(3)");
        $I->see("Pending Confirmation", ".mt-4 .col-md-6:nth-child(2) ul.mb-0 li:nth-child(4)");
        $I->seeNumberOfElements(".mt-4 .col-md-6:nth-child(2) ul.mb-0 li", 4);

        // Confirm the table headers
        $I->see("PDF", "table#invoicesTable thead tr th:nth-child(1)");
        $I->see("#", "table#invoicesTable thead tr th:nth-child(2)");
        $I->see("Account", "table#invoicesTable thead tr th:nth-child(3)");
        $I->see("Amount", "table#invoicesTable thead tr th:nth-child(4)");
        $I->see("Status", "table#invoicesTable thead tr th:nth-child(5)");
        $I->see("Payment Status", "table#invoicesTable thead tr th:nth-child(6)");
        $I->see("Due Date", "table#invoicesTable thead tr th:nth-child(7)");
        $I->see("Actions", "table#invoicesTable thead tr th:nth-child(8)");

        // Confirm the table data
        $I->see($this->invoiceData['number'], "table#invoicesTable tbody tr td:nth-child(2)");
        $I->see($this->accountData['name'], "table#invoicesTable tbody tr td:nth-child(3)");
        $I->see("$100.00", "table#invoicesTable tbody tr td:nth-child(4)");
        $I->see("Opened", "table#invoicesTable tbody tr td:nth-child(5)");
        $I->see("Unpaid", "table#invoicesTable tbody tr td:nth-child(6)");
        $I->see("Due in 30 days", "table#invoicesTable tbody tr td:nth-child(7)");
        $I->see("View", "table#invoicesTable tbody tr td:nth-child(8) ul li");
        $I->see("Pay", "table#invoicesTable tbody tr td:nth-child(8) ul li");

        // change the invoice status to 'paid'
        $I->setInvoiceStatus($this->invoiceData['id'], 4);
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        // When the invoice status is 'paid' or 4 the 'Pay' link should not be displayed and the Due Date should not be displayed
        $I->see("Closed", "table#invoicesTable tbody tr td:nth-child(5)");
        $I->see("Paid", "table#invoicesTable tbody tr td:nth-child(6)");
        $I->dontSee("Due in 30 days", "table#invoicesTable tbody tr td:nth-child(7)");
        $I->see("View", "table#invoicesTable tbody tr td:nth-child(8) ul li");
        $I->dontSee("Pay", "table#invoicesTable tbody tr td:nth-child(8) ul li");
    }

    /**
     * @group frontend
     * @group invoice
     */
    public function ViewInvoicePage(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::INVOICE_VIEW_URL, $this->invoiceData['id']));
        $I->waitForText("Invoice #{$this->invoiceData['number']}", 10, "h1");
        $log_created = date('Y-m-d H:i:s');

        $I->see("Test Item 1");
        $I->see("Test Item 2");

        $I->makeScreenshot("account-center-view-invoice");

        $I->seeInDatabase("jos_mothership_logs", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'user_id' => $this->joomlaUserData['id'],            
            'action' => 'viewed',
            'object_type' => 'invoice',
            'object_id' => $this->accountData['id'],
            'created' => $log_created,
        ]);
    }

    /**
     * @group frontend
     * @group invoice
     */
    public function PayInvoicePage(AcceptanceTester $I)
    {
        // Verify redirection to account center
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        $I->makeScreenshot("account-center-pay-invoice");

        $I->see("Pay", "table#invoicesTable tbody tr td:nth-child(8)");
        $I->click("Pay", "table#invoicesTable tbody tr td:nth-child(8)");
        $I->waitForText("Pay Invoice", 10, "h1");
        $I->amOnPage(sprintf(self::INVOICE_PAY_URL, $this->invoiceData['id']));
        $I->waitForText("Pay Invoice #{$this->invoiceData['number']}", 10, "h1");
        // output the current url into the debug
        codecept_debug($I->grabFromCurrentUrl());
        $I->see("Pay Now");

        $paypal_fee = ($this->invoiceData['total'] * 0.039) + 0.30;
        $total_with_fees = number_format($this->invoiceData['total'] + $paypal_fee,2);

        $I->see("Total Due: \${$this->invoiceData['total']}");
        $I->see("\${$paypal_fee}");
    }

    /**
     * @group frontend
     * @group invoice
     */
    public function InvoicePageViewPdf(AcceptanceTester $I)
    {
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        $I->see("PDF", "table#invoicesTable tbody tr:first-child td:nth-child(1)");
        $I->click("PDF", "table#invoicesTable tbody tr:first-child td:nth-child(1)");
        // How do I switch to the new tab?
        $I->switchToNextTab();
        $I->waitForElement("embed[type='application/pdf']");
        $I->wait(3);

        $I->makeScreenshot("account-center-view-invoice-pdf");
    }
}