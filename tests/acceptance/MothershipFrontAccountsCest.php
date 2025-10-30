<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipFrontAccountsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $mothershipConfig;
    private $joomlaUserData;
    private $invoiceItemData = [];

    const ACCOUNTS_VIEW_ALL_URL = "index.php?option=com_mothership&view=accounts";
    const ACCOUNTS_VIEW_ALL_SEF_URL = "/account-center/accounts/";

    const ACCOUNT_VIEW_URL = "index.php?option=com_mothership&view=account&layout=default&id=%s";
    const ACCOUNT_VIEW_SEF_URL = "/account-center/billing/accounts/%s/";

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->mothershipConfig = $I->setMothershipConfig([
            'company_name' => 'A Fake Company',
            'company_address_1' => '12345 Nowhere St.',
            'company_address_2' => 'Unit 555',
            'company_city' => 'Nowhere',
            'company_state' => 'California',
            'company_zip' => '99999',
            'company_email' => 'test.company@mailinator.com',
            'company_phone' => '555 555-5555',
            'company_default_rate' => '100.00',
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
     * @group account
     * @group frontend-account
     */
    public function ViewAllAccountsPage(AcceptanceTester $I)
    {
        // Verify redirection to account center
        $I->amOnPage(self::ACCOUNTS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Accounts", 10, "h1");
        $I->makeScreenshot("account-center-view-all-accounts");
        $I->dontSee("Warning:");

        // Confirm the correct number of records
        $I->seeNumberOfElements("table#accountsTable tbody tr", 1);

        // Confirm the table headers
        $I->see("#", "table#accountsTable thead tr th:nth-child(1)");
        $I->see("Client", "table#accountsTable thead tr th:nth-child(2)");
        $I->see("Account", "table#accountsTable thead tr th:nth-child(3)");

        // Confirm the table data
        $I->see("{$this->accountData['id']}", "table#accountsTable tbody tr td:nth-child(1)");
        $I->see($this->clientData['name'], "table#accountsTable tbody tr td:nth-child(2)");
        $I->see($this->accountData['name'], "table#accountsTable tbody tr td:nth-child(3)");
    }

    /**
     * @group frontend
     * @group account
     * @group frontend-account
     */
    public function ViewAccountPage(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::ACCOUNT_VIEW_URL, $this->accountData['id']));
        $I->wait(1);
        $log_created = date('Y-m-d H:i:s');
        $I->waitForText($this->accountData['name'], 10, "h1");

        $I->makeScreenshot("account-center-view-account");
        $I->dontSee("Warning:");

        $I->see("Invoices", "h4");
        $I->seeNumberOfElements("table#invoicesTable tbody tr", 1);
        $I->see("PDF", "table#invoicesTable thead tr th:nth-child(1)");
        $I->see("#", "table#invoicesTable thead tr th:nth-child(2)");
        $I->see("Amount", "table#invoicesTable thead tr th:nth-child(3)");
        $I->see("Status", "table#invoicesTable thead tr th:nth-child(4)");
        $I->see("Payment Status", "table#invoicesTable thead tr th:nth-child(5)");
        $I->see("Due Date", "table#invoicesTable thead tr th:nth-child(6)");
        $I->see("Actions", "table#invoicesTable thead tr th:nth-child(7)");

        $I->see("Payments", "h4");
        $I->seeNumberOfElements("table#paymentsTable tbody tr", 1);
        $I->see("#", "table#paymentsTable thead tr th:nth-child(1)");
        $I->see("Amount", "table#paymentsTable thead tr th:nth-child(2)");
        $I->see("Status", "table#paymentsTable thead tr th:nth-child(3)");
        $I->see("Fee Amount", "table#paymentsTable thead tr th:nth-child(4)");
        $I->see("Payment Method", "table#paymentsTable thead tr th:nth-child(5)");
        $I->see("Transaction Id", "table#paymentsTable thead tr th:nth-child(6)");
        $I->see("Invoices", "table#paymentsTable thead tr th:nth-child(7)");
                
        $I->see("", "table#paymentsTable tbody tr");
        $I->see("Projects", "h4");
        $I->seeNumberOfElements("table#projectsTable tbody tr", 1);
        $I->see("Domains", "h4");
        $I->seeNumberOfElements("table#domainsTable tbody tr", 1);

        $created = $I->grabFromDatabase("jos_mothership_logs", "created", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'user_id' => $this->joomlaUserData['id'],            
            'action' => 'viewed',
            'object_type' => 'account',
            'object_id' => $this->accountData['id'],
        ]);

        $timeDifference = abs(strtotime($log_created) - strtotime($created));
        $I->assertLessThanOrEqual(2, $timeDifference, "Log created date should not differ by more than 2 seconds.");
    }

}