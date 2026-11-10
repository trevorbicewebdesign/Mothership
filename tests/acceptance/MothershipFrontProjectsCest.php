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

        // Someone else's project never shows up here.
        $otherClient = $I->createMothershipClient(['name' => 'Another Client']);
        $I->createMothershipProject([
            'client_id' => $otherClient['id'],
            'account_id' => null,
            'name' => 'Not My Project',
            'status' => 1,
        ]);

        $I->amOnPage(self::PROJECTS_VIEW_ALL_URL);
        $I->waitForText("Projects", 10, "h1");
        $I->dontSee("Not My Project");

        $I->takeFullPageScreenshot("account-center-view-all-projects");
        $I->dontSee("Warning:");

        // One client, so this site's list leaves the Client column out.
        $I->seeNumberOfElements("main table#projectsTable tbody tr", 1);
        $I->see("Status", "main table#projectsTable thead tr th:nth-child(1)");
        $I->see("Type", "main table#projectsTable thead tr th:nth-child(2)");
        $I->see("Name", "main table#projectsTable thead tr th:nth-child(3)");
        $I->see("Domain", "main table#projectsTable thead tr th:nth-child(4)");
        $I->see("Created", "main table#projectsTable thead tr th:nth-child(5)");

        $row = 1;
        $I->see("{$projectData['name']}", "main table#projectsTable tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->see(date('M j, Y', strtotime($projectData['created'])), "main table#projectsTable tbody tr:nth-child({$row}) td:nth-child(5)");
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
        $I->waitForText("{$projectData['name']}", 10, "h1");

        $I->takeFullPageScreenshot("account-center-view-project");
        $I->dontSee("Warning:");

        $created = $I->grabFromDatabase("jos_mothership_logs", "created", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'user_id' => $this->joomlaUserData['id'],
            'action' => 'viewed',
            'object_type' => 'project',
            'object_id' => $projectData['id'],
        ]);
        $timeDifference = abs(strtotime($log_created) - strtotime($created));
        $I->assertLessThanOrEqual(2, $timeDifference, "Log created date should not differ by more than 2 seconds.");
    }

    /**
     * @group frontend
     * @group project
     * @group frontend-project
     */
    public function WebsiteProjectShowsSiteDetails(AcceptanceTester $I)
    {
        $projectData = $I->createMothershipProject([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'Example Website',
            'type' => 'website',
            'status' => 1,
            'metadata' => json_encode([
                'primary_url' => 'https://www.example.com',
                'primary_domain' => 'www.example.com',
                'cms_type' => 'joomla',
                'cms_version' => '5.3.1',
                'status' => 'online',
                'plugins' => ['Akeeba Backup' => '9.8.1'],
            ]),
        ]);

        $I->amOnPage(self::PROJECTS_VIEW_ALL_URL);
        $I->waitForText("Projects", 10, "h1");
        $I->dontSee("Warning:");
        $I->see("Online", "main table#projectsTable tbody tr:nth-child(1) td:nth-child(1)");
        $I->see("Joomla 5.3.1", "main table#projectsTable tbody tr:nth-child(1) td:nth-child(2)");
        $I->see("www.example.com", "main table#projectsTable tbody tr:nth-child(1) td:nth-child(3)");
        // The Domain column shows the registrable domain, not the host.
        $I->see("example.com", "main table#projectsTable tbody tr:nth-child(1) td:nth-child(4)");

        $I->amOnPage(sprintf(self::PROJECT_VIEW_URL, $projectData['id']));
        $I->waitForText("Example Website", 10, "h1");
        $I->dontSee("Warning:");
        $I->see("Online", ".ms-proj-meta");
        $I->see("Joomla 5.3.1", ".ms-proj-meta");
        $I->see("Open Joomla Admin");
        $I->see("Akeeba Backup", "table.ms-plugins");
        $I->see("9.8.1", "table.ms-plugins");
    }

    /**
     * @group frontend
     * @group project
     * @group frontend-project
     */
    public function MissingProjectRedirectsToTheList(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::PROJECT_VIEW_URL, 99999));
        $I->waitForText("That project isn’t available on your account.", 10);
        $I->seeInCurrentUrl("view=projects");
    }

    /**
     * @group frontend
     * @group project
     * @group frontend-project
     */
    public function AnotherClientsProjectRedirectsToTheList(AcceptanceTester $I)
    {
        $otherClient = $I->createMothershipClient(['name' => 'Another Client']);
        $otherProject = $I->createMothershipProject([
            'client_id' => $otherClient['id'],
            'account_id' => null,
            'name' => 'Not My Project',
            'status' => 1,
        ]);

        // _before signs in a super user, who may open any project. Sign in a
        // registered user who owns a client of their own instead.
        $registered = $I->createJoomlaUser(['name' => 'Registered Client']);
        $I->updateInDatabase('jos_user_usergroup_map', ['group_id' => 2], ['user_id' => $registered['id']]);
        $I->createMothershipClient([
            'name' => 'Registered Client',
            'owner_user_id' => $registered['id'],
        ]);
        $I->executeInSelenium(function ($webDriver) {
            $webDriver->manage()->deleteAllCookies();
        });
        $I->amOnPage("/");
        $I->fillField(".mod-login input[name=username]", strtolower($registered['username']));
        $I->fillField(".mod-login input[name=password]", '4&GoH#7FvPsY');
        $I->click(".mod-login button[type=submit]");
        $I->waitForText("Hi {$registered['name']},", 10);

        $I->amOnPage(sprintf(self::PROJECT_VIEW_URL, $otherProject['id']));
        $I->waitForText("That project isn’t available on your account.", 10);
        $I->seeInCurrentUrl("view=projects");
        $I->dontSee("Not My Project");
    }
}
