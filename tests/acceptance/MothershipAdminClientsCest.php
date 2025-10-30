<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use DateTime;
use DateTimeZone;


class MothershipAdminClientsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $paymentData;
    private $joomlaUserData;

    const CLIENTS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=clients";
    const CLIENT_EDIT_URL = "/administrator/index.php?option=com_mothership&view=client&layout=edit&id=%s";

    // Toolbar (prefer explicit buttons or tasks)
    public const TBAR            = '#toolbar';
    public const TBAR_NEW        = '#toolbar-new';

    private $form_fields;

    public function _before(AcceptanceTester $I)
    {
        $this->form_fields = [
            'name'=>['type'=>'text','required'=>true],
            'email'=>['type'=>'email','required'=>true],
            'phone'=>['type'=>'tel','required'=>true],
            'address_1'=>['type'=>'text','required'=>true],
            'address_2'=>['type'=>'text','required'=>false],
            'city'=>['type'=>'text','required'=>true],
            'state'=>['type'=>'select','required'=>true],
            'zip'=>['type'=>'text','required'=>true],
            'default_rate'=>['type'=>'text','required'=>true],
            'owner_user_id'=>['type'=>'modal','required'=>true],
        ];

        $I->resetMothershipTables();

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $this->joomlaUserData = $I->createJoomlaUser([
            'name' => 'Test Smith',
        ], 10);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->invoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '100.00',
            'number' => 1000,
            'created' => date('Y-m-d'),
            'status' => 1,
        ]);

        $this->paymentData = $I->createMothershipPaymentData([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
        ]);

        $I->amOnPage("/administrator/");
        $I->fillField("input[name=username]", "admin");
        $I->fillField("input[name=passwd]", "password123!test");
        $I->click("Log in");
        $I->waitForText("Hide Forever");
        $I->click("Hide Forever");
    }

    /**
     * @group backend
     * @group client
     * @group backend-client
     */
    public function MothershipViewClients(AcceptanceTester $I)
    {
        $I->amOnPage(self::CLIENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Clients", 30, "h1.page-title");
        $I->makeScreenshot("mothership-clients-view-all");
        $I->validateJoomlaItemActions(['New', ], $I);
        $I->see("Client Name Asc");
        $I->validateJoomlaViewAllTableHeaders([
            'Id'=>2, 
            'Name'=>3, 
            'Phone'=>4, 
            'Default Rate'=>5, 
            'Created'=>6
        ], $I);
        $I->validateJoomlaViewAllTableRowData(1, [
            'Id' => ['value' => $this->clientData['id'], 'position' => 2],
            'Name' => ['value' => 'Test Client', 'position' => 3],
            'Phone' => ['value' => $this->clientData['phone'], 'position' => 4],
            'Default Rate' => ['value' => '$100.00', 'position' => 5],
            'Created' => ['value' => date("Y-m-d"), 'position' => 6],
        ], $I);
        $I->validateJoomlaViewAllNumberRows(1, $I);
        $I->see("1 - 1 / 1 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group client
     * @group backend-client
     */
    public function MothershipAddClient(AcceptanceTester $I)
    {       
        $I->amOnPage(self::CLIENTS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Clients", 30, "h1.page-title");

        $I->seeElement(self::TBAR." ".self::TBAR_NEW);
        $I->see("New", self::TBAR." ".self::TBAR_NEW);

        $I->click(self::TBAR." ".self::TBAR_NEW);
        $I->wait(1);
        $I->waitForText("Mothership: New Client", 30, "h1.page-title");

        $I->makeScreenshot("mothership-client-add-new");
        $I->dontSee("Warning");

        $I->see("Save", self::TBAR);
        $I->see("Save & Close", self::TBAR);
        $I->see("Cancel", self::TBAR);

        $I->seeElement("form[name=adminForm]");
        $I->seeElement("form#client-form");

        $I->seeElement("#myTab");
        $I->see("Client Details", "#myTab");
        
        $I->validateJoomlaFormFieldsExist($this->form_fields, $I);

        // TEST Error Validation - Submit empty form
        $I->click("Save", self::TBAR);
        $I->wait(1);
        $I->waitForText("Mothership: New Client", 30, "h1.page-title");
        $I->makeScreenshot("mothership-client-add-errors");
        // The form cannot be submitted as it's missing required data.
        // Please correct the marked fields and try again.
        $I->see("The form cannot be submitted as it's missing required data. Please correct the marked fields and try again.", ".alert-message");
        $I->validateJoomlaFormErrors($this->form_fields, $I);

        $form_data = [
            'name' => 'Another Client',
            'email' => 'another.client@mailinator.com',
            'phone' => '(555) 555-5555',
            'address_1' => '12345 St.',
            'address_2' => 'APT 123',
            'city' => 'City',
            'state' => 'California',
            'zip' => '95524',
            'default_rate' => '100.00',
        ];
        // Fill in the form fields
        $I->fillJoomlaForm($this->form_fields, $form_data, $I);

        // Add Owner User
        $I->click(".icon-user");
        $I->wait(1);
        $I->makeScreenshot("mothership-client-add-contact");
        $I->switchToIFrame(".iframe-content");       
        $I->fillField("#filter_search", $this->joomlaUserData['name']);
        $I->click('//button[contains(@class, "btn") and .//span[contains(@class, "icon-search")]]');
        $I->wait(3);
        $I->click($this->joomlaUserData['name']);
        $I->wait(1);
        $I->switchToIFrame();
        $I->makeScreenshot("mothership-client-add-filled");

        // TEST ACTION Save
        $I->click("Save", self::TBAR);
        $I->wait(1);
        $I->waitForText("Mothership: Edit Client", 30, "h1.page-title");
        $I->waitForText("Client Another Client saved successfully.", 30, ".alert-message");
        // TEST ACTION Save & Close
        $I->click("Save & Close", self::TBAR);
        $I->waitForText("Mothership: Clients", 30, "h1.page-title");
        $I->seeInCurrentUrl(("/administrator/index.php?option=com_mothership&view=clients"));
        $I->see("Client saved", ".alert-message");
        $I->seeInCurrentUrl(self::CLIENTS_VIEW_ALL_URL);
        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);
        $client_id = $I->grabTextFrom("#j-main-container table tbody tr:nth-child(1) td:nth-child(2)");
        $I->see($client_id . "", "#j-main-container table tbody tr:nth-child(1) td:nth-child(2)");
        $I->see("Another Client", "#j-main-container table tbody tr:nth-child(1) td:nth-child(3)");
        // $I->see((new DateTime('now', new DateTimeZone('America/Los_Angeles')))->format('Y-m-d'), "#j-main-container table tbody tr:nth-child(1) td:nth-child(6)");

        $I->seeInDatabase("jos_mothership_clients", [
            'name' => 'Another Client',
            'email' => 'another.client@mailinator.com',
            'phone' => '(555) 555-5555',
            'address_1' => '12345 St.',
            'address_2' => 'APT 123',
            'city' => 'City',
            'state' => 'CA',
            'zip' => '95524',
            'default_rate' => '100.00',
            'tax_id' => '',
            // 'created' => date("Y-m-d 00:00:00"),
        ]);

        // Open the Client again and confirm the data is correct
        $I->amOnPage(sprintf(self::CLIENT_EDIT_URL, $client_id));
        $I->click("Details");
        // Confirm the value in jform_number is correct
        $I->seeInField("input#jform_name", "Another Client");
        // TEST ACTION Close
        $I->click("Close", self::TBAR);
        $I->wait(1);
        $I->waitForText("Mothership: Clients", 30, "h1.page-title");
        $I->seeInCurrentUrl(self::CLIENTS_VIEW_ALL_URL);
        $I->dontSeeElement("span.icon-checkedout");

    }

    /**
     * @group backend
     * @group client
     * @group backend-client
     */
    public function MothershipEditInvalidClient(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::CLIENT_EDIT_URL, "9999"));
        $I->wait(1);
        $I->waitForText('Mothership: Clients', 30, 'h1.page-title');
        $I->waitForText("Client not found. Please select a valid client.", 30, "#system-message-container .alert-message");
        $I->seeInCurrentUrl(self::CLIENTS_VIEW_ALL_URL);
    }

    /**
     * @group backend
     * @group client
     * @group delete
     * @group backend-client
     */
    public function MothershipDeleteClientWithAccountsFailure(AcceptanceTester $I)
    {
        $I->seeInDatabase("jos_mothership_clients", [
            'id' => $this->clientData['id'],
        ]);
        $I->seeInDatabase("jos_mothership_accounts", [
            'client_id' => $this->clientData['id'],
        ]);
        $I->amOnPage(self::CLIENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Clients", 30, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);
        
        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "clients.delete"]);

        $I->click("Delete", self::TBAR);
        $I->wait(1);

        $I->seeInCurrentUrl(self::CLIENTS_VIEW_ALL_URL);
        $I->see("Mothership: Clients", "h1.page-title");
        $I->see("Cannot delete client(s) [1] because they have one or more associated accounts.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);
        $I->seeInDatabase("jos_mothership_clients", [
            'id' => $this->clientData['id'],
        ]);
        $I->seeInDatabase("jos_mothership_accounts", [
            'client_id' => $this->clientData['id'],
        ]);
    }

    /**
     * @group backend
     * @group client
     * @group delete
     * @group backend-client
     */
    public function MothershipDeleteClientSuccess(AcceptanceTester $I)
    {
        $noAccountsClient = $I->createMothershipClient([
            'name' => 'No Accounts Client',
        ]);

        $I->amOnPage(self::CLIENTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Clients", 30, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);
        
        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "clients.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "client.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "clients.delete"]);

        $I->click("Delete", self::TBAR);
        $I->wait(1);

        $I->seeInCurrentUrl(self::CLIENTS_VIEW_ALL_URL);
        $I->see("Mothership: Clients", "h1.page-title");
        $I->see("Cannot delete client(s) [1] because they have one or more associated accounts.", ".alert-message");
        $I->see("1 Client deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);

        $I->seeInDatabase("jos_mothership_clients", [
            'id' => $this->clientData['id'],
        ]);
        $I->seeInDatabase("jos_mothership_accounts", [
            'client_id' => $this->clientData['id'],
        ]);
        $I->dontSeeInDatabase('jos_mothership_clients', [
            'id' => $noAccountsClient['id'],
        ]);
    }
}