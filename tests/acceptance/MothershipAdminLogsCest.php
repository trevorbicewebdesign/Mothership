<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use DateTime;
use DateTimeZone;


class MothershipAdminLogsCest
{
    private $logData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $paymentData;
    private $joomlaUserData;

    const LOGS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=logs";
    const LOG_EDIT_URL = "/administrator/index.php?option=com_mothership&view=log&layout=edit&id=%s";

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->logData = $I->createMothershipLog([
            'description' => 'Test Log',
            'details' => 'Test Log Details',
        ]);

        $I->amOnPage("/administrator/");
        $I->fillField("input[name=username]", "adminuser");
        $I->fillField("input[name=passwd]", "password123!test");
        $I->click("Log in");
        $I->wait(3);
    }

    /**
     * @group backend
     * @group log
     */
    public function MothershipViewLogs(AcceptanceTester $I)
    {
        $I->amOnPage(self::LOGS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");

        $I->makeScreenshot("mothership-logs-view-all");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");

        $I->see("Log Name Asc");

        $I->see("Id", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Name", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("Phone", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Default Rate", "#j-main-container table thead tr th:nth-child(5)");
        $I->see("Created", "#j-main-container table thead tr th:nth-child(6)");

        $I->see("1", "#j-main-container table tbody tr td:nth-child(2)");
        $I->see("Test Log", "#j-main-container table tbody tr td:nth-child(3)");
        $I->see($this->logData['phone'], "#j-main-container table tbody tr td:nth-child(4)");
        $I->see("$100.00", "#j-main-container table tbody tr td:nth-child(5)");
        $I->see(date("Y-m-d"), "#j-main-container table tbody tr td:nth-child(6)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 1);

        $I->see("1 - 1 / 1 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group log
     */
    public function MothershipAddLog(AcceptanceTester $I)
    {
        $I->amOnPage(self::LOGS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->click("{$toolbar} {$toolbarNew} .btn.button-new");
        $I->waitForText("Mothership: New Log", 20, "h1.page-title");

        $I->makeScreenshot("mothership-log-add-details");

        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        $I->seeElement("form[name=adminForm]");
        $I->seeElement("form#log-form");

        $I->seeElement("#myTab");
        $I->see("Log Details", "#myTab");

        $I->seeElement("input#jform_name");
        $I->seeElement("input#jform_email");
        $I->seeElement("input#jform_phone");
        $I->seeElement("input#jform_address_1");
        $I->seeElement("input#jform_address_2");
        $I->seeElement("input#jform_city");
        $I->seeElement("select#jform_state");
        $I->seeElement("input#jform_zip");
        $I->seeElement("input#jform_default_rate");
        $I->seeElement("input#jform_owner_user_id");

        $I->fillField("input#jform_name", "Another Log");
        $I->fillField("input#jform_email", "another.log@mailinator.com");
        $I->fillField("input#jform_phone", "(555) 555-5555");
        $I->fillField("input#jform_address_1", "12345 St.");
        $I->fillField("input#jform_address_2", "APT 123");
        $I->fillField("input#jform_city", "City");
        $I->selectOption("select#jform_state", "California");
        $I->fillField("input#jform_zip", "95524");
        $I->fillField("input#jform_default_rate", "100.00");

        $I->click(".icon-user");
        $I->makeScreenshot("mothership-log-add-contact");
        $I->switchToIFrame(".iframe-content");       
        $I->fillFIeld("#filter_search", $this->joomlaUserData['name']);
        $I->click('//button[contains(@class, "btn") and .//span[contains(@class, "icon-search")]]');
        $I->wait(3);
        $I->click($this->joomlaUserData['name']);
        $I->wait(1);
        $I->switchToIFrame();

        $I->click("Save & Close", "#toolbar");
        $I->wait(3);
        $I->seeInCurrentUrl(("/administrator/index.php?option=com_mothership&view=logs"));
        $I->see("Log saved", ".alert-message");

        $I->seeInCurrentUrl(self::LOGS_VIEW_ALL_URL);
        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);

        $log_id = $I->grabTextFrom("#j-main-container table tbody tr:nth-child(1) td:nth-child(2)");

        $I->see($log_id . "", "#j-main-container table tbody tr:nth-child(1) td:nth-child(2)");
        $I->see("Another Log", "#j-main-container table tbody tr:nth-child(1) td:nth-child(3)");
        $I->see((new DateTime('now', new DateTimeZone('America/Los_Angeles')))->format('Y-m-d'), "#j-main-container table tbody tr:nth-child(1) td:nth-child(6)");

        $I->seeInDatabase("jos_mothership_logs", [
            'name' => 'Another Log',
            'email' => 'another.log@mailinator.com',
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
        $I->amOnPage(sprintf(self::LOG_EDIT_URL, $log_id));
        $I->click("Details");
        // Confirm the value in jform_number is correct
        $I->seeInField("input#jform_name", "Another Log");
        $I->click("Save", "#toolbar");

        $I->wait(1);
        $I->see("Mothership: Edit Log", "h1.page-title");
        $I->seeCurrentUrlEquals(sprintf(self::LOG_EDIT_URL, $log_id));
        $I->see("Log Another Log saved successfully.", ".alert-message");
    }

    /**
     * @group backend
     * @group log
     * @group delete
     */
    public function MothershipDeleteLogWithAccountsFailure(AcceptanceTester $I)
    {
        $I->seeInDatabase("jos_mothership_logs", [
            'id' => $this->logData['id'],
        ]);
        $I->seeInDatabase("jos_mothership_accounts", [
            'log_id' => $this->logData['id'],
        ]);
        $I->amOnPage(self::LOGS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);
        
        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "logs.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::LOGS_VIEW_ALL_URL);
        $I->see("Mothership: Logs", "h1.page-title");
        $I->see("Cannot delete log(s) [1] because they have one or more associated accounts.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);
        $I->seeInDatabase("jos_mothership_logs", [
            'id' => $this->logData['id'],
        ]);
        $I->seeInDatabase("jos_mothership_accounts", [
            'log_id' => $this->logData['id'],
        ]);
    }

    /**
     * @group backend
     * @group log
     * @group delete
     */
    public function MothershipDeleteLogSuccess(AcceptanceTester $I)
    {
        $noAccountsLog = $I->createMothershipLog([
            'name' => 'No Accounts Log',
        ]);

        $I->amOnPage(self::LOGS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);
        
        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "logs.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "log.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "logs.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::LOGS_VIEW_ALL_URL);
        $I->see("Mothership: Logs", "h1.page-title");
        $I->see("Cannot delete log(s) [1] because they have one or more associated accounts.", ".alert-message");
        $I->see("1 Log deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);

        $I->seeInDatabase("jos_mothership_logs", [
            'id' => $this->logData['id'],
        ]);
        $I->seeInDatabase("jos_mothership_accounts", [
            'log_id' => $this->logData['id'],
        ]);
        $I->dontSeeInDatabase('jos_mothership_logs', [
            'id' => $noAccountsLog['id'],
        ]);
    }
}