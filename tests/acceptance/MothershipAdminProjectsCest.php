<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;


class MothershipAdminProjectsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $invoiceItemData = [];
    private $projectData;

    const PROJECTS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=projects";
    const PROJECT_EDIT_URL = "/administrator/index.php?option=com_mothership&view=project&layout=edit&id=%s";
    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $clientData2 = $I->createMothershipClient([
            'name' => 'Acme Inc.',
        ]);

        $accountData2 = $I->createMothershipAccount([
            'client_id' => $clientData2['id'],
            'name' => 'Roadrunner Products',
        ]);

        $this->userData = $I->createMothershipUser([
            'user_id' => '43',
            'client_id' => $this->clientData['id'],
        ]);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->projectData = $I->createMothershipProject([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'Example Website',
            'type' => 'website',
            'status' => 1,
            'metadata' => json_encode([
                "status"=>"online", 
                "cms_type"=>"joomla", 
                "cms_version"=>"5.3.0", 
                "favicon_url"=>"", 
                "primary_url"=>"https://example.com", 
                "primary_domain"=>"example.com", 
                "staging_url"=>NULL, 
                "dev_url"=>NULL, 
                "under_construction"=>"0"
            ]),
        ]);

        // Navigate to the login page
        $I->amOnPage("/administrator/");

        // Log in with valid credentials
        $I->fillField("input[name=username]", "admin");
        $I->fillField("input[name=passwd]", "password123!test");
        $I->click("Log in");
        $I->waitForText("Hide Forever");
        $I->click("Hide Forever");
    }


    /**
     * @group backend
     * @group project
     * @group backend-project
     */
    public function MothershipViewProjects(AcceptanceTester $I)
    {
        $I->amOnPage(self::PROJECTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Projects", 20, "h1.page-title");

        $I->makeScreenshot("mothership-view-projects");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");
        
        $I->see("ID", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Name", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("Client", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Account", "#j-main-container table thead tr th:nth-child(5)");        
        $I->see("Created", "#j-main-container table thead tr th:nth-child(6)");
        $I->see("Type", "#j-main-container table thead tr th:nth-child(7)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 1);

        $I->see("{$this->projectData['id']}", "#j-main-container table tbody tr td:nth-child(2)");
        $I->see("{$this->projectData['name']}", "#j-main-container table tbody tr td:nth-child(3)");
        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr td:nth-child(4)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr td:nth-child(5)");
        $I->see(date("Y-m-d", strtotime($this->projectData['created'])), "#j-main-container table tbody tr td:nth-child(6)");
        $I->see("WEBSITE", "#j-main-container table tbody tr td:nth-child(7)");

        $I->see("1 - 1 / 1 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group project
     * @group backend-project
     */
    public function MothershipAddProject(AcceptanceTester $I)
    {
        $I->amOnPage(self::PROJECTS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Projects", 20, "h1.page-title");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");
        $I->click("{$toolbar} {$toolbarNew} .btn.button-new");
        $I->wait(1);
        $I->waitForText("Mothership: New Project", 20, "h1.page-title");

        $I->makeScreenshot("mothership-add-project");

        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        $I->seeElement("select#jform_client_id");
        $I->dontSeeElement("select#jform_account_id");
        
        $I->seeElement("input#jform_name");
        $I->seeElement("#jform_metadata_primary_url");
        $I->seeElement("input#jform_metadata_primary_domain");
        $I->seeElement("input#jform_metadata_cms_type");
        $I->seeElement("input#jform_metadata_cms_version");
        $I->seeElement("select#jform_metadata_status");
        $I->seeElement("#jform_metadata_under_construction");
        $I->seeElement("input#jform_metadata_staging_url");
        $I->seeElement("input#jform_metadata_dev_url");
        $I->seeElement("input#jform_metadata_favicon_url");

        // Attempt to save the form without filling out any fields
        $I->click("Save", "#toolbar");
        $I->wait(1);

        $I->waitForText("The form cannot be submitted as it's missing required data.", 20);
        $I->see("Please correct the marked fields and try again.");
        
        $I->see("One of the options must be selected", "label#jform_client_id-lbl .form-control-feedback");
        
        $I->amGoingTo("Fill out the form");

        $I->selectOption("select#jform_client_id", $this->clientData['id']);
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_client_id", "{$this->clientData['name']}");
        $I->selectOption("select#jform_account_id", $this->accountData['id']);
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_account_id", "{$this->accountData['name']}");

        $I->fillField("input#jform_name", "Example Website 2");
        $I->fillFIeld("input#jform_metadata_primary_url", "https://example.com");
        $I->fillFIeld("input#jform_metadata_primary_domain", "example.com");
        $I->fillField("input#jform_metadata_cms_type", "joomla");
        $I->fillField("input#jform_metadata_cms_version", "5.3.0");
        $I->selectOption("select#jform_metadata_status", "online");
        
        $I->click("Save", "#toolbar");
        $I->waitForText("Project Example Website 2 saved successfully.", 5, "#system-message-container .alert-message");
        $currentUrl = $I->grabFromCurrentUrl();
        codecept_debug($currentUrl);
        $I->waitForText("Mothership: Edit Project", 5, "h1.page-title");
        $I->seeInCurrentUrl( sprintf(self::PROJECT_EDIT_URL, ($this->projectData['id']+1)) );

        $metadata = json_decode($I->grabFromDatabase("jos_mothership_projects", "metadata", ['id' => ($this->projectData['id']+1)]));
        codecept_debug($metadata);

        
        $I->seeInDatabase("jos_mothership_projects", [
            'id' => ($this->projectData['id']+1),
            'name' => 'Example Website 2',
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'type' => 'website',
        ]);

        if($metadata!== null && is_array($metadata)) {
            $I->assertArrayHasKey('primary_url', $metadata);
            $I->assertArrayHasKey('primary_domain', $metadata);
            $I->assertArrayHasKey('cms_type', $metadata);
            $I->assertArrayHasKey('cms_version', $metadata);
            $I->assertArrayHasKey('status', $metadata);
            // $I->assertArrayHasKey('under_construction', $metadata);
            $I->assertArrayHasKey('staging_url', $metadata);
            $I->assertArrayHasKey('dev_url', $metadata);
            $I->assertArrayHasKey('favicon_url', $metadata);

            $I->assertEquals("https://example.com", $metadata['primary_url']);
            $I->assertEquals("example.com", $metadata['primary_domain']);
            $I->assertEquals("joomla", $metadata['cms_type']);
            $I->assertEquals("5.3.0", $metadata['cms_version']);
            $I->assertEquals("online", $metadata['status']);
        }
    }

    /**
     * @group backend
     * @group project
     * @group backend-project
     */
    public function MothershipEditInvalidProject(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::PROJECT_EDIT_URL, "9999"));
        $I->waitForText("Project not found. Please select a valid project.", 10, "#system-message-container .alert-error");
    }

    /**
     * @group backend
     * @group project
     * @group scan
     * @group backend-project
     */
    public function MothershipScanProject(AcceptanceTester $I)
    {
       
        $I->seeInDatabase("jos_mothership_projects", [ 'id' => $this->projectData['id'] ]);

        $I->amOnPage( sprintf(self::PROJECT_EDIT_URL, $this->projectData['id']) );
        $I->waitForText("Mothership: Edit Project", 10, "h1.page-title");

        $I->see("Project Scan & Update", "joomla-toolbar-button#toolbar-refresh");
        $I->seeElement("joomla-toolbar-button#toolbar-refresh", ['task' => "project.mothershipScan"]);

        $I->click("Project Scan & Update", "#toolbar");
        $I->waitForText("Project Example Website scan completed successfully.", 10, ".alert-message");
        $I->seeInCurrentUrl( sprintf(self::PROJECT_EDIT_URL, $this->projectData['id']));

        $project= $I->grabProjectFromDatabase($this->projectData['id']);
        codecept_debug($project);

        $I->seeInDatabase("jos_mothership_projects", [
            'id' => $this->projectData['id'],
            'name' => $this->projectData['name'],
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
        ]);

        $project_meta = json_decode($I->grabFromDatabase("jos_mothership_projects", "metadata", ['id' => $this->projectData['id']]), true);
        codecept_debug($project_meta);

        $I->assertArrayHasKey('primary_url', $project_meta);
        $I->assertArrayHasKey('primary_domain', $project_meta);
        $I->assertArrayHasKey('cms_type', $project_meta);
        $I->assertArrayHasKey('cms_version', $project_meta);
        $I->assertArrayHasKey('status', $project_meta);
        // $I->assertArrayHasKey('under_construction', $project_meta);
        $I->assertArrayHasKey('staging_url', $project_meta);
        $I->assertArrayHasKey('dev_url', $project_meta);
        $I->assertArrayHasKey('favicon_url', $project_meta);


        $I->seeInDatabase("jos_mothership_logs", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'object_type' => 'project',
            'object_id' => $this->projectData['id'],
            'action' => 'scanned',
        ]);
    }

    /**
     * @group backend
     * @group project
     * @group delete
     * @group backend-project
     */
    public function MothershipDeleteProject(AcceptanceTester $I)
    {
        $projectData = $I->createMothershipProject([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'Test Project',
            'status' => 1,
        ]);
        $I->seeInDatabase("jos_mothership_projects", [ 'id' => $projectData['id'] ]);
        $I->amOnPage(self::PROJECTS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Projects", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 2);

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "projects.checkin"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "project.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "projects.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);
        $I->waitForText("Mothership: Projects", 10, "h1.page-title");
        $I->seeInCurrentUrl(self::PROJECTS_VIEW_ALL_URL);
        $I->see("2 Projects deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 0);

        $I->dontSeeInDatabase("jos_mothership_projects", [ 'id' => $projectData['id'] ]);
    }
}