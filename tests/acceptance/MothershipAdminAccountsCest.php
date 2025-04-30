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

    const ACCOUNTS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=accounts";
    const ACCOUNT_EDIT_URL = "/administrator/index.php?option=com_mothership&view=account&layout=edit&id=%s";

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
        $I->waitForText("Hide Forever", 20);
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
        $I->waitForText("Mothership: Accounts", 20, "h1.page-title");

        $I->click("Test Client");
        $I->waitForText("Mothership: Edit Client", 20, "h1.page-title");
        $I->click("Close", "#toolbar");
        $I->waitForText("Mothership: Accounts", 20, "h1.page-title");
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
        $I->waitForText("Mothership: Accounts", 20, "h1.page-title");
        
        $I->makeScreenshot("mothership-accounts-view-all");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");
        $I->see("Id", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Name", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("Client", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Created", "#j-main-container table thead tr th:nth-child(5)");

        $I->see("1", "#j-main-container table tbody tr td:nth-child(2)");
        $I->see("Test Account", "#j-main-container table tbody tr td:nth-child(3)");
        $I->see("Test Client", "#j-main-container table tbody tr td:nth-child(4)");
        // $I->see(date("Y-m-d"), "#j-main-container table tbody tr td:nth-child(5)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 1);
    }

    /**
     * @group backend
     * @group account
     * @group backend-account
     */
    public function MothershipAddAccount(AcceptanceTester $I)
    {
        $I->amOnPage(self::ACCOUNTS_VIEW_ALL_URL);
        $I->wait(2);
        $I->waitForText("Mothership: Accounts", 20, "h1.page-title");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->click("{$toolbar} {$toolbarNew} .btn.button-new");
        $I->wait(1);
        $I->see("Mothership: New Account", "h1.page-title");

        $I->makeScreenshot("mothership-account-add-details");

        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        $I->seeElement("form[name=adminForm]");
        $I->seeElement("form#account-form");

        $I->seeElement("#myTab");
        $I->see("Account Details", "#myTab button[aria-controls=details]");

        $I->seeElement("select#jform_client_id");
        $I->seeElement("input#jform_name");

        $I->selectOption("select#jform_client_id", "Test Client");
        $I->fillField("input#jform_name", "Test Account");

        $I->click("Save", "#toolbar");
        $I->wait(1);

        $I->see("Mothership: Edit Account", "h1.page-title");
        $I->see("Account Test Account saved", ".alert-message");

        $I->seeInField("input#jform_name", "Test Account");
        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");

        $I->seeInDatabase("jos_mothership_accounts", [
            'name' => 'Test Account',
            'client_id' => $this->clientData['id'],
            // 'created' => date("Y-m-d 00:00:00"),
        ]);

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
        $I->waitForText("Mothership: Accounts", 20, "h1.page-title");

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

        $I->click("Delete", "#toolbar");
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
        $I->waitForText("Mothership: Accounts", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "accounts.delete"]);

        $I->click("Delete", "#toolbar");
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
        $I->waitForText("Mothership: Accounts", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);


        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "accounts.delete"]);

        $I->click("Delete", "#toolbar");
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