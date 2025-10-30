<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use DateTime;
use DateTimeZone;


class MothershipAdminAccountsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $paymentData;
    private $joomlaUserData;

    const TBAR = "#toolbar";
    const TBAR_NEW = "#toolbar-new";

    const ACCOUNTS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=accounts";
    const ACCOUNT_EDIT_URL = "/administrator/index.php?option=com_mothership&view=account&layout=edit&id=%s";
    private $form_fields;

    public function _before(AcceptanceTester $I)
    {
        $this->form_fields = [
            'client_id'=>['type'=>'select','required'=>true],
            'name'=>['type'=>'text','required'=>true],
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
        $I->waitForText("Hide Forever", 30);
        $I->click("Hide Forever");
;    }

    /**
     * @group backend
     * @group account
     * @group backend-account
     */
    public function MothershipCancelClientEdit(AcceptanceTester $I)
    {   
        $I->amOnPage( self::ACCOUNTS_VIEW_ALL_URL);
        $I->waitForJoomlaHeading("Accounts", $I);
        $I->click("Test Client");

        $I->waitForJoomlaHeading("Edit Client", $I);
        $I->click("Close", "#toolbar");

        $I->waitForJoomlaHeading("Accounts", $I);
        $I->seeCurrentUrlEquals(self::ACCOUNTS_VIEW_ALL_URL);
    }

    /**
     * @group backend
     * @group account
     * @group backend-account
     */
    public function MothershipViewAccounts(AcceptanceTester $I)
    {
        $I->amOnPage(self::ACCOUNTS_VIEW_ALL_URL);
        $I->waitForJoomlaHeading("Accounts", $I);
        $I->makeScreenshot("mothership-accounts-view-all");
        $I->dontSee("Warning");
        $I->validateJoomlaItemActions(['New', ], $I);
        $I->validateJoomlaViewAllTableHeaders([
            'Id'=>2, 
            'Name'=>3, 
            'Client'=>4, 
            'Created'=>5
        ], $I);
        $I->validateJoomlaViewAllTableRowData(1, [
            'Id' => ['value' => $this->clientData['id'], 'position' => 2],
            'Name' => ['value' => 'Test Account', 'position' => 3],
            'Client' => ['value' => $this->clientData['name'], 'position' => 4],
            'Created' => ['value' => date("Y-m-d"), 'position' => 5],
        ], $I);
        $I->validateJoomlaViewAllNumberRows(1, $I);
        $I->see("1 - 1 / 1 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group account
     * @group backend-account
     */
    public function MothershipAddAccount(AcceptanceTester $I)
    {
        $I->amOnPage(self::ACCOUNTS_VIEW_ALL_URL);
        $I->waitForJoomlaHeading("Accounts", $I);
        $I->validateJoomlaItemActions([
            'New', 
        ], $I);

        // Add a new account
        $I->click("New");
        $I->waitForJoomlaHeading("New Account", $I);
        $I->makeScreenshot("mothership-account-add-empty");
        $I->dontSee("Warning:");

        // CHECK FOR TOOLBAR ACTIONS
        $I->validateJoomlaItemActions([
            'Save', 
            'Save & Close', 
            'Cancel'
        ], $I);

        // Check that the tab exists
        $I->seeElement("#myTab");
        $I->see("Account Details", "#myTab button[aria-controls=details]");
        // Validate the form and fields exist
        $I->validateJoomlaForm("account-form", $this->form_fields, $I);

        // TEST Error Validation - Submit empty form
        $I->click("Save", self::TBAR);
        $I->waitForJoomlaHeading("New Account", $I);
        $I->makeScreenshot("mothership-account-add-errors");
        $I->see("The form cannot be submitted as it's missing required data. Please correct the marked fields and try again.", ".alert-message");
        $I->validateJoomlaFormErrors($this->form_fields, $I);

        $form_data = [
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ];
        $I->fillJoomlaForm($this->form_fields, $form_data, $I);

        // TEST ACTION SAVE
        $I->click("Save", self::TBAR);
        $I->waitForJoomlaHeading("Edit Account", $I);
        $I->see("Account Test Account saved", ".alert-message");
        $I->seeInField("input#jform_name", "Test Account");
        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");

        $I->seeInDatabase("jos_mothership_accounts", [
            'name' => 'Test Account',
            'client_id' => $this->clientData['id'],
        ]);

        $I->click("Save & Close", self::TBAR);
        $I->waitForJoomlaHeading("Accounts", $I);
        $I->seeCurrentUrlEquals(self::ACCOUNTS_VIEW_ALL_URL);
        $I->see("Account saved", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);

        $account_id = $I->grabTextFrom("#j-main-container table tbody tr:nth-child(1) td:nth-child(2)");

        // TEST ACTION CANCEL
        $I->amOnPage(sprintf(self::ACCOUNT_EDIT_URL, $account_id));
        $I->waitForJoomlaHeading("Edit Account", $I);
        $I->click("Close", self::TBAR);
        $I->waitForJoomlaHeading("Accounts", $I);
        $I->seeInCurrentUrl(self::ACCOUNTS_VIEW_ALL_URL);
        $I->dontSeeElement("span.icon-checkedout");
    }

    /**
     * @group backend
     * @group account
     * @group backend-account
     */
    public function MothershipEditInvalidAccount(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::ACCOUNT_EDIT_URL, "9999"));
        $I->wait(1);
        $I->waitForText('Mothership: Accounts', 30, 'h1.page-title');
        $I->seeInCurrentUrl(self::ACCOUNTS_VIEW_ALL_URL);
        $I->waitForText("Account not found. Please select a valid account.", 30, "#system-message-container .alert-message");
    }

    /**
     * @group backend
     * @group account
     * @group delete
     * @group backend-account
     */
    public function MothershipDeleteAccount(AcceptanceTester $I)
    {
        $clientData = $I->createMothershipClient([
            'name' => 'Test Client 2',
        ]);
        $accountData = $I->createMothershipAccount([
            'client_id' => $clientData['id'],
            'name' => 'Test Account 2',
        ]);
        $I->seeInDatabase("jos_mothership_accounts", [ 'id' => $accountData['id'] ]);
        $I->amOnPage(self::ACCOUNTS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Accounts", 30, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "accounts.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "account.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "accounts.delete"]);

        $I->click("Delete", self::TBAR);
        $I->wait(1);

        $I->seeInCurrentUrl(self::ACCOUNTS_VIEW_ALL_URL);
        $I->see("Mothership: Accounts", "h1.page-title");
        $I->see("1 Account deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);

        $I->dontSeeInDatabase("jos_mothership_accounts", [ 'id' => $accountData['id'] ]);
    }

    /**
     * @group backend
     * @group account
     * @group delete
     * @group backend-account
     */
    public function MothershipDeleteAccountFailure(AcceptanceTester $I)
    {
        $I->seeInDatabase("jos_mothership_accounts", [ 'id' => $this->accountData['id'] ]);
        $I->amOnPage(self::ACCOUNTS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Accounts", 30, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "accounts.delete"]);

        $I->click("Delete", self::TBAR);
        $I->wait(1);

        $I->seeInCurrentUrl(self::ACCOUNTS_VIEW_ALL_URL);
        $I->see("Mothership: Accounts", "h1.page-title");
        $I->see("Cannot delete account(s) [1] because they have one or more associated invoices.", ".alert-message");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);

        $I->seeInDatabase("jos_mothership_accounts", [ 'id' => $this->accountData['id'] ]);
    }

    /**
     * @group backend
     * @group account
     * @group delete
     * @group backend-account
     */
    public function MothershipDeleteAccountWithPaymentUnlinksPayment(AcceptanceTester $I)
    {
        $clientData = $I->createMothershipClient(['name' => 'Test Client 3']);
        $accountData = $I->createMothershipAccount([
            'client_id' => $clientData['id'],
            'name' => 'Test Account 3',
        ]);
        $paymentData = $I->createMothershipPayment([
            'client_id' => $clientData['id'],
            'account_id' => $accountData['id'],
        ]);

        $I->seeInDatabase("jos_mothership_payments", [
            'id' => $paymentData['id'],
            'account_id' => $accountData['id'],
        ]);

        $I->amOnPage(self::ACCOUNTS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Accounts", 30, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);


        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "accounts.delete"]);

        $I->click("Delete", self::TBAR);
        $I->wait(1);

        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);

        $I->see("1 Account deleted successfully.", ".alert-message");
        $I->dontSeeInDatabase("jos_mothership_accounts", ['id' => $accountData['id']]);

        // Make sure the payment still exists, but account_id is now NULL
        $I->seeInDatabase("jos_mothership_payments", [
            'id' => $paymentData['id'],
            'account_id' => null,
            'client_id' => $clientData['id'],
        ]);
    }
}