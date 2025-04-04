<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class AccountCenterInvoicesCest
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
    public function accountCenterInvoicesPage(AcceptanceTester $I)
    {
        // Verify redirection to account center
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        $I->makeScreenshot("account-center-view-all-invoices");

        // Confirm the correct number of records
        $I->seeNumberOfElements("table#invoicetable tbody tr", 1);

        $I->see("Invoice Status Legend", ".mt-4");
        $I->see("Opened", ".mt-4 ul.mb-0 li:nth-child(1)");
        $I->see("Late", ".mt-4 ul.mb-0 li:nth-child(2)");
        $I->see("Paid", ".mt-4 ul.mb-0 li:nth-child(3)");
        $I->seeNumberOfElements(".mt-4 ul.mb-0 li", 3);

        // Confirm the table headers
        $I->see("PDF", "table#invoicetable thead tr th:nth-child(1)");
        $I->see("#", "table#invoicetable thead tr th:nth-child(2)");
        $I->see("Account", "table#invoicetable thead tr th:nth-child(3)");
        $I->see("Amount", "table#invoicetable thead tr th:nth-child(4)");
        $I->see("Status", "table#invoicetable thead tr th:nth-child(5)");
        $I->see("Payment Status", "table#invoicetable thead tr th:nth-child(6)");
        $I->see("Due Date", "table#invoicetable thead tr th:nth-child(7)");
        $I->see("Actions", "table#invoicetable thead tr th:nth-child(8)");

        // Confirm the table data
        $I->see($this->invoiceData['number'], "table#invoicetable tbody tr td:nth-child(2)");
        $I->see($this->accountData['name'], "table#invoicetable tbody tr td:nth-child(3)");
        $I->see("$100.00", "table#invoicetable tbody tr td:nth-child(4)");
        $I->see("Opened", "table#invoicetable tbody tr td:nth-child(5)");
        $I->see("Unpaid", "table#invoicetable thead tr th:nth-child(6)");
        $I->see("Due in 30 days", "table#invoicetable tbody tr td:nth-child(7)");
        $I->see("View", "table#invoicetable tbody tr td:nth-child(8) ul li");
        $I->see("Pay", "table#invoicetable tbody tr td:nth-child(8) ul li");

        // change the invoice status to 'paid'
        $I->setInvoiceStatus($this->invoiceData['id'], 4);
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        // When the invoice status is 'paid' or 4 the 'Pay' link should not be displayed and the Due Date should not be displayed
        $I->see("Paid", "table#invoicetable tbody tr td:nth-child(5)");
        $I->dontSee("Due in 30 days", "table#invoicetable tbody tr td:nth-child(6)");
        $I->see("View", "table#invoicetable tbody tr td:nth-child(7) ul li");
        $I->dontSee("Pay", "table#invoicetable tbody tr td:nth-child(7) ul li");
    }

    /**
     * @group frontend
     * @group invoice
     */
    public function accountCenterViewInvoicePage(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::INVOICE_VIEW_URL, $this->invoiceData['id']));
        $I->waitForText("Invoice #{$this->invoiceData['number']}", 10, "h1");

        $I->see("Test Item 1");
        $I->see("Test Item 2");

        $I->makeScreenshot("account-center-view-invoice");
    }

    /**
     * @group frontend
     * @group invoice
     */
    public function accountCenterPayInvoicePage(AcceptanceTester $I)
    {
        // Verify redirection to account center
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        $I->makeScreenshot("account-center-pay-invoice");

        $I->see("Pay", "table#invoicetable tbody tr td:nth-child(7)");
        $I->click("Pay", "table#invoicetable tbody tr td:nth-child(7)");
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
    public function accountCenterInvoicePageViewPdf(AcceptanceTester $I)
    {
        $I->amOnPage(self::INVOICES_VIEW_ALL_URL);
        $I->waitForText("Invoices", 10, "h1");

        $I->see("PDF", "table#invoicetable tbody tr:first-child td:nth-child(1)");
        $I->click("PDF", "table#invoicetable tbody tr:first-child td:nth-child(1)");
        // How do I switch to the new tab?
        $I->switchToNextTab();
        $I->waitForElement("embed[type='application/pdf']");
        $I->wait(3);

        $I->makeScreenshot("account-center-view-invoice-pdf");
    }
}