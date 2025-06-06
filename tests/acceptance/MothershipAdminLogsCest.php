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

    const LOG_ACCOUNT_VIEWED_DESCRIPTION = "Account `%s` was viewed.";
    const LOG_ACCOUNT_VIEWED_DETAILS = "Account `%s` was viewed by user %s.";
    const LOG_PROJECT_VIEWED_DESCRIPTION = "Project ID %s was viewed.";
    const LOG_PROJECT_VIEWED_DETAILS = "Project ID %s was viewed by user %s.";
    const LOG_DOMAIN_VIEWED_DESCRIPTION = "Domain `%s` was viewed.";
    const LOG_DOMAIN_VIEWED_DETAILS = "Domain `%s` was viewed by user %s.";
    const LOG_INVOICE_VIEWED_DESCRIPTION = "Invoice ID %s was viewed.";
    const LOG_INVOICE_VIEWED_DETAILS = "Invoice ID %s was viewed by user %s.";
    const LOG_PAYMENT_VIEWED_DESCRIPTION = "Payment ID %s was viewed.";
    const LOG_PAYMENT_VIEWED_DETAILS = "Payment ID %s was viewed by user %s.";

    const LOG_PAYMENT_STATUS_CHANGED_DESCRIPTION = "Payment status changed from `%s` to `%s`.";
    const LOG_PAYMENT_STATUS_CHANGED_DETAILS = "Payment ID %s status changed from `%s` to `%s` by user %s.";
    const LOG_PAYMENT_INITIATED_DESCRIPTION = "Payment ID %d was initiated.";
    const LOG_PAYMENT_INITIATED_DETAILS = "Payment ID %d using method %s was initiated by user %s to pay invoice %s.";

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);
        $j = 0;

        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => 548,
            'object_id'   => $this->accountData['id'],
            'object_type' => 'account',
            'action'      => 'viewed',
            'meta'        => json_encode([]),
            'created'     => '2025-04-11 01:29:03',
        ]);
        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_ACCOUNT_VIEWED_DESCRIPTION, $this->accountData['name']);
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_ACCOUNT_VIEWED_DETAILS, $this->accountData['name'], 548);
        $j++;

        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => 548,
            'object_id'   => 93,
            'object_type' => 'payment',
            'action'      => 'viewed',
            'meta'        => json_encode([]),
            'created'     => '2025-04-11 01:29:03',
        ]);
        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_VIEWED_DESCRIPTION, 93);
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_VIEWED_DETAILS, 93, 548);
        $j++;
      
        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => 548,
            'object_id'   => 2,
            'object_type' => 'invoice',
            'action'      => 'viewed',
            'meta'        => json_encode([]),
            'created'     => '2025-04-11 01:45:19',
        ]);

        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_INVOICE_VIEWED_DESCRIPTION, 2);
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_INVOICE_VIEWED_DETAILS, 2, 548);
        $j++;
        
        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => 548,
            'object_id'   => 1,
            'object_type' => 'domain',
            'action'      => 'viewed',
            'meta'        => json_encode([]),
            'created'     => '2025-04-21 21:34:08',
        ]);

        $this->logTextDescription[$this->logData[$j]['id']] = sprintf(self::LOG_DOMAIN_VIEWED_DESCRIPTION, 1);
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_DOMAIN_VIEWED_DETAILS, 1, 548);
        $j++;

        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => 548,
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
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_STATUS_CHANGED_DETAILS, 93, 'Completed', 'Pending', 548);
        $j++;

        $this->logData[] = $I->createMothershipLog([
            'client_id'   => $this->clientData['id'],
            'account_id'  => $this->accountData['id'],
            'user_id'     => 548,
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
        $this->logTextDetails[$this->logData[$j]['id']] = sprintf(self::LOG_PAYMENT_INITIATED_DETAILS, 97, 'paypal', 548, 2);
        $j++;

        $I->amOnPage("/administrator/");
        $I->fillField("input[name=username]", "admin");
        $I->fillField("input[name=passwd]", "password123!test");
        $I->click("Log in");
        $I->waitForText("Hide Forever");
        $I->click("Hide Forever");
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

        $I->makeScreenshot("mothership-logs-view-all");

        $toolbar = "#toolbar";
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
       
        $I->see("1 - 6 / 6 items", "#j-main-container .pagination__wrapper");
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

        $I->makeScreenshot("mothership-log-view");

        $toolbar = "#toolbar";
        $toolbarCancel = "#toolbar-cancel";
        $I->seeElement("{$toolbar} {$toolbarCancel}");
        $I->see("Close", "{$toolbar} {$toolbarCancel} .btn.button-cancel");

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
        
        $I->click("Close", "#toolbar");
        $I->wait(1);
        $I->waitForText("Mothership: Logs", 20, "h1.page-title");
        
    }

}