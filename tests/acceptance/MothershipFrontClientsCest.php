<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipFrontClientsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $mothershipConfig;
    private $joomlaUserData;
    private $invoiceItemData = [];

    const CLIENTS_VIEW_ALL_URL = "index.php?option=com_mothership&view=clients";
    const CLIENTS_VIEW_ALL_SEF_URL = "/account-center/clients/";

    const CLIENT_VIEW_URL = "index.php?option=com_mothership&view=client&layout=default&id=%s";
    const CLIENT_VIEW_SEF_URL = "/account-center/billing/clients/%s/";

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
     * @group client
     * @group frontend-client
     */
    public function ViewAllClientsPage(AcceptanceTester $I)
    {
        // Verify redirection to account center
        $I->amOnPage(self::CLIENTS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Clients", 10, "h1");

        $I->makeScreenshot("account-center-view-all-clients");

        // Confirm the correct number of records
        $I->seeNumberOfElements("table#clientsTable tbody tr", 1);

        // Confirm the table headers
        $I->see("#", "table#clientsTable thead tr th:nth-child(1)");
        $I->see("Client", "table#clientsTable thead tr th:nth-child(2)");

        // Confirm the table data
        $I->see("{$this->clientData['id']}", "table#clientsTable tbody tr td:nth-child(1)");
        $I->see($this->clientData['name'], "table#clientsTable tbody tr td:nth-child(2)");
    }

    /**
     * @group frontend
     * @group client
     * @group frontend-client
     */
    public function ViewClientPage(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::CLIENT_VIEW_URL, $this->clientData['id']));
        $I->wait(1);
        $log_created = date('Y-m-d H:i:s');
        $I->waitForText($this->clientData['name'], 10, "h1");

        $I->makeScreenshot("account-center-view-client");

        $I->see("Email: {$this->clientData['email']}");
        $I->see("Phone: {$this->clientData['phone']}");
        $I->see("Address: {$this->clientData['address_1']}");
        $I->see("Address: {$this->clientData['address_2']}");
        $I->see("Location: {$this->clientData['city']}, {$this->clientData['state']} {$this->clientData['zip']}");
        $I->see("Default Rate: {$this->clientData['default_rate']}");
        $I->see("Created: {$this->clientData['created']}");
    }

}