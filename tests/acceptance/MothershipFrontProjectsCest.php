<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipFrontProjectsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $mothershipConfig;
    private $joomlaUserData;
    private $paymentData;
    private $invoicePaymentData;

    private $invoiceItemData = [];
    const PROJECTS_VIEW_ALL_URL = "index.php?option=com_mothership&view=projects";
    const PROJECT_VIEW_URL = "index.php?option=com_mothership&view=project&id=%s";

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
            'testmode' => 1,
        ]);

        $this->joomlaUserData = $I->createJoomlaUser([
            'name' => 'Test Client',
        ], 8);

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
            'due_date' => date('Y-m-d', strtotime('-1 day')),
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

        $this->paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => '100.00',
            'payment_date' => date('Y-m-d H:i:s'),
            'fee_amount' => '6.00',
            'fee_passed_on' => 0,
            'payment_method' => 'paypal',
            'transaction_id' => '123456',
            'status' => 1,
        ]);

        $this->invoicePaymentData = $I->createMothershipInvoicePayment([
            'invoice_id' => $this->invoiceData['id'],
            'payment_id' => $this->paymentData['id'],
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
     * @group project
     * @group frontend-project
     */
    public function ViewAllProjectsPage(AcceptanceTester $I)
    {
        $projectData = $I->createMothershipProject([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'Test Project',
            'status' => 1,
        ]);

        // Verify redirection to account center
        $I->amOnPage(self::PROJECTS_VIEW_ALL_URL);
        $I->waitForText("Projects", 10, "h1");
        $I->makeScreenshot("account-center-view-all-projects");

        // Confirm the correct number of records
        $I->seeNumberOfElements("main table#projectsTable tbody tr", 1);

        // Confirm the table headers
        $I->see("#", "main table#projectsTable thead tr th:nth-child(1)");
        $I->see("Name", "main table#projectsTable thead tr th:nth-child(2)");
        $I->see("Account", "main table#projectsTable thead tr th:nth-child(3)");
        $I->see("Type", "main table#projectsTable thead tr th:nth-child(4)");
        $I->see("Status", "main table#projectsTable thead tr th:nth-child(5)");
        $I->see("Created", "main table#projectsTable thead tr th:nth-child(6)");

        // Confirm the table data
        $row = 1;
        $I->see("{$projectData['id']}", "main table#projectsTable tbody tr:nth-child({$row}) td:nth-child(1)");
        $I->see("{$projectData['name']}", "main table#projectsTable tbody tr:nth-child({$row}) td:nth-child(2)");
        $I->see("{$this->accountData['name']}", "main table#projectsTable tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->see("{$projectData['type']}", "main table#projectsTable tbody tr:nth-child({$row}) td:nth-child(4)");
        $I->see("active", "main table#projectsTable tbody tr:nth-child({$row}) td:nth-child(5)");
        $I->see("{$projectData['created']}", "main table#projectsTable tbody tr:nth-child({$row}) td:nth-child(6)");

    }

    /**
     * @group frontend
     * @group project
     * @group frontend-project
     */
    public function ViewProjectPage(AcceptanceTester $I)
    {
        $projectData = $I->createMothershipProject([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'Test Project',
            'status' => 1,
        ]);
        $I->amOnPage(sprintf(self::PROJECT_VIEW_URL, $projectData['id']));
        $log_created = date('Y-m-d H:i:s');
        $I->waitForText("{$projectData['name']}", 10);

        // Capture a screenshot of the view
        $I->makeScreenshot("account-center-view-project");

        $created = $I->grabFromDatabase("jos_mothership_logs", "created", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'user_id' => $this->joomlaUserData['id'],
            'action' => 'viewed',
            'object_type' => 'project',
            'object_id' => $this->accountData['id'],
        ]);

        $timeDifference = abs(strtotime($log_created) - strtotime($created));
        $I->assertLessThanOrEqual(2, $timeDifference, "Log created date should not differ by more than 2 seconds.");
    }

}