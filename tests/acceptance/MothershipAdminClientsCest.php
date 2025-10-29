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

    public function _before(AcceptanceTester $I)
    {
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

    private function fillByType(\AcceptanceTester $I, string $type, string $selector, $value): void
    {
        switch ($type) {
            case 'select':
                $I->selectOption($selector, $value);
                break;
            case 'hidden':
                // usually set via picker/modal; skip manual fill
                break;
            default:
                $I->fillField($selector, (string) $value);
        }
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

        $I->seeElement(self::TBAR." ".self::TBAR_NEW);
        $I->see("New", self::TBAR." ".self::TBAR_NEW);

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");

        $I->see("Client Name Asc");

        $I->see("Id", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Name", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("Phone", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Default Rate", "#j-main-container table thead tr th:nth-child(5)");
        $I->see("Created", "#j-main-container table thead tr th:nth-child(6)");

        $I->see("1", "#j-main-container table tbody tr td:nth-child(2)");
        $I->see("Test Client", "#j-main-container table tbody tr td:nth-child(3)");
        $I->see($this->clientData['phone'], "#j-main-container table tbody tr td:nth-child(4)");
        $I->see("$100.00", "#j-main-container table tbody tr td:nth-child(5)");
        $I->see(date("Y-m-d"), "#j-main-container table tbody tr td:nth-child(6)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 1);

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

        $I->makeScreenshot("mothership-client-add-details");
        $I->dontSee("Warning");

        $I->see("Save", self::TBAR);
        $I->see("Save & Close", self::TBAR);
        $I->see("Cancel", self::TBAR);

        $I->seeElement("form[name=adminForm]");
        $I->seeElement("form#client-form");

        $I->seeElement("#myTab");
        $I->see("Client Details", "#myTab");

        // Define the form fields
        $form_fields = [
            'name'=>'text',
            'email'=>'email',
            'phone'=>'tel',
            'address_1'=>'text',
            'address_2'=>'text',
            'city'=>'text',
            'state'=>'select',
            'zip'=>'text',
            'default_rate'=>'text',
            'owner_user_id'=>'modal',
        ];
        $required_fields = [
            'name',
            'email',
            'phone',
            'address_1',
            'city',
            'state',
            'zip',
            'default_rate',
            'owner_user_id',
        ];
        // Verify fields exist
        foreach($form_fields as $field=> $type) {
            switch($type){
                case 'select':
                    $I->seeElement("select#jform_{$field}");
                    break;
                default:
                    $I->seeElement("input#jform_{$field}");
            }
        }

        // TEST Error Validation - Submit empty form
        $I->click("Save", self::TBAR);
        $I->wait(1);
        $I->waitForText("Mothership: New Client", 30, "h1.page-title");
        // The form cannot be submitted as it's missing required data.
        // Please correct the marked fields and try again.
        $I->see("The form cannot be submitted as it's missing required data. Please correct the marked fields and try again.", ".alert-message");
        foreach($form_fields as $field=> $type) {
             if(!in_array($field, $required_fields)){
                continue;
            }
            switch($type){
                case 'select':
                    $I->see("One of the options must be selected", "#jform_{$field}-lbl");
                    $I->seeElement("select#jform_{$field}.invalid[aria-invalid=true]");
                    break;
                case 'modal':
                default:
                    $I->see("Please fill in this field", "#jform_{$field}-lbl");
                    $I->seeElement("input#jform_{$field}.invalid[aria-invalid=true]");
            }
        }

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
            // 'owner_user_id' => $this->joomlaUserData['name']
        ];

        // Fill in the form fields
        foreach ($form_fields as $field => $type) {
           if(!in_array($field, array_keys($form_data))){
                continue;
            }
            switch($type){
                case 'select':
                    $I->selectOption("select#jform_{$field}", $form_data[$field]);
                    break;
                default:
                    $I->fillField("input#jform_{$field}", $form_data[$field]);
            }
        }
        // Add Owner User
        $I->click(".icon-user");
        $I->makeScreenshot("mothership-client-add-contact");
        $I->switchToIFrame(".iframe-content");       
        $I->fillField("#filter_search", $this->joomlaUserData['name']);
        $I->click('//button[contains(@class, "btn") and .//span[contains(@class, "icon-search")]]');
        $I->wait(3);
        $I->click($this->joomlaUserData['name']);
        $I->wait(1);
        $I->switchToIFrame();

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

        // Open the Invoice again and confirm the data is correct
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
        $I->seeInCurrentUrl(self::CLIENTS_VIEW_ALL_URL);
        $I->waitForText("Client not found. Please select a valid client.", 30, "#system-message-container .alert-message");
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