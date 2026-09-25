<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use DateTime;
use DateTimeZone;


class MothershipAdminLogsCest
{
    private $logData=[];
    private $userData;
    private $clientData;
    private $accountData;
    private $invoiceData;
    private $paymentData;
    private $joomlaUserData;
    private $logTextDescription = [];
    private $logTextDetails = [];

    const LOGS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=logs";
    const LOG_EDIT_URL = "/administrator/index.php?option=com_mothership&view=log&layout=edit&id=%s";
    const LOG_TASK_URL = "/administrator/index.php?option=com_mothership&task=%s&id=%s";

    const TBAR = "#toolbar";

    // A log written by a Joomla user shows that user's name. A log whose user no
    // longer exists shows "#<id>", and a guest log shows "a visitor".
    const DELETED_USER_ID = 548;
    const DELETED_USER_LABEL = "#548";
    const GUEST_LABEL = "a visitor";

    const LOG_READ_ONLY_MESSAGE = "Log entries are a record of what happened and cannot be edited.";

    const LOG_ACCOUNT_VIEWED_DESCRIPTION = "Account `%s` was viewed.";
    const LOG_ACCOUNT_VIEWED_DETAILS = "Account `%s` was viewed by %s.";
    const LOG_PROJECT_VIEWED_DESCRIPTION = "Project `%s` was viewed.";
    const LOG_PROJECT_VIEWED_DETAILS = "Project `%s` was viewed by %s.";
    const LOG_DOMAIN_VIEWED_DESCRIPTION = "Domain `%s` was viewed.";
    const LOG_DOMAIN_VIEWED_DETAILS = "Domain `%s` was viewed by %s.";
    const LOG_INVOICE_VIEWED_DESCRIPTION = "Invoice ID %s was viewed.";
    const LOG_INVOICE_VIEWED_DETAILS = "Invoice ID %s was viewed by %s.";
    const LOG_PAYMENT_VIEWED_DESCRIPTION = "Payment ID %s was viewed.";
    const LOG_PAYMENT_VIEWED_DETAILS = "Payment ID %s was viewed by %s.";

    const LOG_PAYMENT_STATUS_CHANGED_DESCRIPTION = "Payment status changed from `%s` to `%s`.";
    const LOG_PAYMENT_STATUS_CHANGED_DETAILS = "Payment ID %s status changed from `%s` to `%s` by %s.";
    const LOG_PAYMENT_INITIATED_DESCRIPTION = "Payment ID %d was initiated.";
    const LOG_PAYMENT_INITIATED_DETAILS = "Payment ID %d using method %s was initiated by %s to pay invoice %s.";

    public function _before(AcceptanceTester $I)
    {
        // Codeception reuses this object for every test in the class, so the
        // fixtures must be rebuilt from scratch rather than appended to.
        $this->logData = [];
        $this->logTextDescription = [];
        $this->logTextDetails = [];

        $I->resetMothershipTables();

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        // The user whose name should appear in the "Details" column.
        $this->joomlaUserData = $I->createJoomlaUser([
            'name' => 'Test Smith',
            'username' => 'testsmith',
        ]);
        $userId = $this->joomlaUserData['id'];
        $userName = $this->joomlaUserData['name'];

        $j = 0;

        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => $userId,
            'object_id'   => $this->accountData['id'],
            'object_type' => 'account',
            'action'      => 'viewed',
            'meta'        => json_encode([]),
            'created'     => '2025-04-11 01:29:03',
        ]);
        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_ACCOUNT_VIEWED_DESCRIPTION, $this->accountData['name']);
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_ACCOUNT_VIEWED_DETAILS, $this->accountData['name'], $userName);
        $j++;

        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => $userId,
            'object_id'   => 93,
            'object_type' => 'payment',
            'action'      => 'viewed',
            'meta'        => json_encode([]),
            'created'     => '2025-04-11 01:29:03',
        ]);
        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_VIEWED_DESCRIPTION, 93);
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_VIEWED_DETAILS, 93, $userName);
        $j++;

        // Written by a user that has since been deleted: falls back to "#<id>".
        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => self::DELETED_USER_ID,
            'object_id'   => 2,
            'object_type' => 'invoice',
            'action'      => 'viewed',
            'meta'        => json_encode([]),
            'created'     => '2025-04-11 01:45:19',
        ]);
        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_INVOICE_VIEWED_DESCRIPTION, 2);
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_INVOICE_VIEWED_DETAILS, 2, self::DELETED_USER_LABEL);
        $j++;

        // Written by a guest (no user): falls back to "a visitor".
        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => 0,
            'object_id'   => 1,
            'object_type' => 'domain',
            'action'      => 'viewed',
            'meta'        => json_encode([]),
            'created'     => '2025-04-21 21:34:08',
        ]);
        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_DOMAIN_VIEWED_DESCRIPTION, 1);
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_DOMAIN_VIEWED_DETAILS, 1, self::GUEST_LABEL);
        $j++;

        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => $userId,
            'object_id'   => 93,
            'object_type' => 'payment',
            'action'      => 'status_changed',
            'meta'        => json_encode([
                'new_status' => 'Pending',
                'old_status' => 'Completed',
            ]),
            'created'     => '2025-04-11 01:32:21',
        ]);
        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_STATUS_CHANGED_DESCRIPTION, 'Completed', 'Pending');
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_STATUS_CHANGED_DETAILS, 93, 'Completed', 'Pending', $userName);
        $j++;

        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => $userId,
            'object_id'   => 97,
            'object_type' => 'payment',
            'action'      => 'initiated',
            'meta'        => json_encode([
                'invoice_id'     => 2,
                'payment_method' => 'Paypal',
            ]),
            'created'     => '2025-04-11 01:59:16',
        ]);
        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_INITIATED_DESCRIPTION, 97);
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_INITIATED_DETAILS, 97, 'Paypal', $userName, 2);
        $j++;

        $I->loginAsAdmin();
    }

    /**
     * @group backend
     * @group log
     * @group backend-log
     */
    public function MothershipViewAllLogs(AcceptanceTester $I)
    {
        $I->amOnPage(self::LOGS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");

        $I->takeFullPageScreenshot("mothership-logs-view-all");

        $toolbar = self::TBAR;
        $toolbarNew = "#toolbar-new";
        // New Logs Can't Be Created
        $I->dontSeeElement("{$toolbar} {$toolbarNew}");
        $I->dontSee("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", count($this->logData));

        $I->see("ID", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Client Name", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("Account Name", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Description", "#j-main-container table thead tr th:nth-child(5)");
        $I->see("Details", "#j-main-container table thead tr th:nth-child(6)");
        $I->see("Object Type", "#j-main-container table thead tr th:nth-child(7)");
        $I->see("Object ID", "#j-main-container table thead tr th:nth-child(8)");
        $I->see("Action", "#j-main-container table thead tr th:nth-child(9)");
        $I->see("Created", "#j-main-container table thead tr th:nth-child(10)");

        foreach ($this->logData as $log) {
            $I->see("{$log['id']}", "#j-main-container table tbody");
            $I->see($this->clientData['name'], "#j-main-container table tbody");
            $I->see($this->accountData['name'], "#j-main-container table tbody");
            $I->see($this->logTextDescription[$log['id']], "#j-main-container table tbody");
            $I->see($this->logTextDetails[$log['id']], "#j-main-container table tbody");
            $I->see("{$log['object_type']}", "#j-main-container table tbody");
            $I->see("{$log['object_id']}", "#j-main-container table tbody");
            $I->see("{$log['action']}", "#j-main-container table tbody");
            $I->see("{$log['created']}", "#j-main-container table tbody");
        }

        // The "Details" column names the user rather than printing a raw user id.
        $I->dontSee("by user ", "#j-main-container table tbody");
        $I->dontSee("by {$this->joomlaUserData['id']}.", "#j-main-container table tbody");

        $I->see("1 - 6 / 6 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * Logs are an audit trail: the Actions dropdown offers Delete only.
     *
     * @group backend
     * @group log
     * @group backend-log
     */
    public function MothershipLogsToolbarIsDeleteOnly(AcceptanceTester $I)
    {
        $I->amOnPage(self::LOGS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");

        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "logs.delete"]);

        $I->dontSeeElement("joomla-toolbar-button#status-group-children-edit");
        $I->dontSeeElement("joomla-toolbar-button#status-group-children-checkin");
        $I->dontSee("Edit", self::TBAR);
        $I->dontSee("Check-in", self::TBAR);

        $I->takeFullPageScreenshot("mothership-logs-toolbar-actions");
    }

    /**
     * @group backend
     * @group log
     * @group backend-log
     */
    public function MothershipViewLog(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::LOG_EDIT_URL, $this->logData[0]['id']));
        $I->wait(1);
        $I->waitForText("Mothership: View Log", 20, "h1.page-title");

        $I->takeFullPageScreenshot("mothership-log-view");

        $toolbar = self::TBAR;
        $toolbarCancel = "#toolbar-cancel";
        $I->seeElement("{$toolbar} {$toolbarCancel}");
        $I->see("Close", "{$toolbar} {$toolbarCancel} .btn.button-cancel");

        // Read only: nothing to save.
        $I->dontSeeElement("{$toolbar} #toolbar-apply");
        $I->dontSeeElement("{$toolbar} #toolbar-save");
        $I->dontSee("Save", $toolbar);

        $I->seeElement("select#jform_client_id");
        $I->seeElement("select#jform_account_id");
        $I->seeElement("input#jform_user_id");
        $I->seeElement("input#jform_object_type");
        $I->seeElement("input#jform_object_id");
        $I->seeElement("input#jform_action");
        $I->seeElement("input#jform_created");

        $I->seeOptionIsSelected("select#jform_client_id", $this->clientData['name']);
        $I->seeOptionIsSelected("select#jform_account_id", $this->accountData['name']);
        $I->seeInField("input#jform_user_id", "{$this->logData[0]['user_id']}");
        $I->seeInField("input#jform_object_type", "{$this->logData[0]['object_type']}");
        $I->seeInField("input#jform_object_id", "{$this->logData[0]['object_id']}");
        $I->seeInField("input#jform_action", "{$this->logData[0]['action']}");
        $I->seeInField("input#jform_created", "{$this->logData[0]['created']}");

        $I->click("Close", self::TBAR);
        $I->wait(1);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");

    }

    /**
     * The log.edit and log.save tasks refuse and send the user back to the list.
     *
     * @group backend
     * @group log
     * @group backend-log
     */
    public function MothershipLogEditAndSaveAreRefused(AcceptanceTester $I)
    {
        $log = $this->logData[0];

        foreach (['log.edit', 'log.save'] as $task) {
            $I->amOnPage(sprintf(self::LOG_TASK_URL, $task, $log['id']));
            $I->wait(1);
            $I->waitForText("Mothership: Logs", 20, "h1.page-title");

            $I->seeInCurrentUrl("view=logs");
            $I->dontSee("Mothership: View Log", "h1.page-title");
            $I->see(self::LOG_READ_ONLY_MESSAGE, ".alert-message");
        }

        $I->takeFullPageScreenshot("mothership-log-edit-refused");

        // Nothing about the entry changed.
        $I->seeInDatabase("jos_mothership_logs", [
            'id'          => $log['id'],
            'user_id'     => $log['user_id'],
            'object_type' => $log['object_type'],
            'object_id'   => $log['object_id'],
            'action'      => $log['action'],
            'created'     => $log['created'],
        ]);
    }

    /**
     * @group backend
     * @group log
     * @group backend-log
     */
    public function MothershipDeleteSingleLog(AcceptanceTester $I)
    {
        $log = $this->logData[0];

        $I->seeInDatabase("jos_mothership_logs", ['id' => $log['id']]);

        $I->amOnPage(self::LOGS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", count($this->logData));
        $I->seeElement(".btn-toolbar");

        $I->checkOption("#j-main-container table tbody input[name='cid[]'][value='{$log['id']}']");
        $I->click("Actions");
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "logs.delete"]);

        $I->click("Delete", self::TBAR);
        $I->wait(1);

        $I->seeInCurrentUrl("view=logs");
        $I->see("Mothership: Logs", "h1.page-title");
        $I->see("1 log entry deleted.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", count($this->logData) - 1);
        $I->dontSee($this->logTextDetails[$log['id']], "#j-main-container table tbody");

        $I->takeFullPageScreenshot("mothership-logs-delete-single");

        $I->dontSeeInDatabase("jos_mothership_logs", ['id' => $log['id']]);
        foreach (array_slice($this->logData, 1) as $remaining) {
            $I->seeInDatabase("jos_mothership_logs", ['id' => $remaining['id']]);
        }
    }

    /**
     * @group backend
     * @group log
     * @group backend-log
     */
    public function MothershipDeleteMultipleLogs(AcceptanceTester $I)
    {
        $toDelete = array_slice($this->logData, 0, 2);
        $toKeep = array_slice($this->logData, 2);

        $I->amOnPage(self::LOGS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", count($this->logData));

        foreach ($toDelete as $log) {
            $I->checkOption("#j-main-container table tbody input[name='cid[]'][value='{$log['id']}']");
        }
        $I->click("Actions");
        $I->click("Delete", self::TBAR);
        $I->wait(1);

        $I->seeInCurrentUrl("view=logs");
        $I->see("Mothership: Logs", "h1.page-title");
        $I->see(count($toDelete) . " log entries deleted.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", count($toKeep));

        $I->takeFullPageScreenshot("mothership-logs-delete-multiple");

        foreach ($toDelete as $log) {
            $I->dontSeeInDatabase("jos_mothership_logs", ['id' => $log['id']]);
            $I->dontSee($this->logTextDetails[$log['id']], "#j-main-container table tbody");
        }
        foreach ($toKeep as $log) {
            $I->seeInDatabase("jos_mothership_logs", ['id' => $log['id']]);
            $I->see($this->logTextDetails[$log['id']], "#j-main-container table tbody");
        }
    }

}
