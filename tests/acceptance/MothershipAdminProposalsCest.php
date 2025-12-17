<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;


class MothershipAdminProposalsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $proposalData;
    private $projectData;
    private $proposalItemData = [];
    private $mothershipConfig = [];

    const PROPOSALS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=proposals";
    const PROPOSAL_EDIT_URL = "/administrator/index.php?option=com_mothership&view=proposal&layout=edit&id=%s";
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

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
            'email' => 'test.client@mailinator.com',
            'default_rate' => '111.00',
            'owner_user_id' => '1',
        ]);

        $this->userData = $I->createMothershipUser([
            'user_id' => '1',
            'client_id' => $this->clientData['id'],
        ]);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->projectData = $I->createMothershipProject([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'Test Project',
        ]);

        $clientData2 = $I->createMothershipClient([
            'name' => 'Acme Inc.',
        ]);

        $accountData2 = $I->createMothershipAccount([
            'client_id' => $clientData2['id'],
            'name' => 'Roadrunner Products',
        ]);

        $this->proposalData = $I->createMothershipProposal([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => $this->projectData['id'],   
            'total' => '175.00',
            'number' => 1000,
            'created' => date('Y-m-d H:i:s'),
            'status' => 1,
        ]);

        $this->proposalItemData[] = $I->createMothershipProposalItem([
            'proposal_id' => $this->proposalData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);

        $this->proposalItemData[] = $I->createMothershipProposalItem([
            'proposal_id' => $this->proposalData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'quantity' => '1',
            'rate' => '70.00',
            'subtotal' => '70.00',
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
     * @group proposal
     * @group backend-proposal
     */
    public function MothershipViewProposals(AcceptanceTester $I)
    {

        $proposalData = $I->createMothershipProposal([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => NULL,
            'total' => '475.00',
            'number' => 1004,
            'created' => date('Y-m-d H:i:s'),
            'status' => 4,
            'locked' => 1,
        ]);
        
        $proposalItemData = [];
        $proposalItemData[] = $I->createMothershipProposalItem(array(
            'proposal_id'   => $proposalData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'time_low' => '01:00',
            'time' => '01:30',
            'quantity_low'  => 1,
            'quantity' => 1.5,
            'rate' => 70,
            'subtotal_low'  => 70,
            'subtotal'      => 105,
        ));

        $proposalItemData[] = $I->createMothershipProposalItem(array(
            'proposal_id'   => $proposalData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'time_low' => '01:00',
            'time' => '01:00',
            'quantity_low'  => 1,
            'quantity' => 1,
            'rate' => 70,
            'subtotal_low'  => 70,
            'subtotal' => 70,
        ));
        
        $I->amOnPage(self::PROPOSALS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Proposals", 30, "h1.page-title");

        $I->makeScreenshot("mothership-view-proposals");

        $I->dontSee("Warning");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");
        
        $I->see("ID", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Proposal Number", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("Title", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("PDF", "#j-main-container table thead tr th:nth-child(5)");
        $I->see("Client", "#j-main-container table thead tr th:nth-child(6)");
        $I->see("Account", "#j-main-container table thead tr th:nth-child(7)");
        $I->see("Project", "#j-main-container table thead tr th:nth-child(8)");
        $I->see("Total", "#j-main-container table thead tr th:nth-child(9)");
        $I->see("Status", "#j-main-container table thead tr th:nth-child(10)");
        $I->see("Created", "#j-main-container table thead tr th:nth-child(11)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $row = 1;
        // This proposal is not locked, so it should not have the lock icon
        $I->dontSeeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(1) i.fa-solid.fa-lock");
        $I->see("{$this->proposalData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(2)");
        $I->see("{$this->proposalData['number']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->seeNumberOfElements("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a", 2);
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a.downloadPdf");
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a.previewPdf");

        $downloadPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a.downloadPdf", 'href');
        $previewPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a.previewPdf", 'href');

        $I->assertEquals("/administrator/index.php?option=com_mothership&task=proposal.downloadPdf&id={$this->proposalData['id']}", $downloadPdfUrl);
        $I->assertEquals("/administrator/index.php?option=com_mothership&task=proposal.previewPdf&id={$this->proposalData['id']}", $previewPdfUrl);

        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(6)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(7)");
        $I->see("{$this->projectData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(8)");
        $I->see("{$this->proposalData['total']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(9)");
        $I->see(date('Y-m-d'), "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(11)");

        $row = 2;
        // This proposal IS not locked, so it SHOULD have the lock icon
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(1) i.fa-solid.fa-lock");
        $I->see("{$proposalData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(2)");
        $I->see("{$proposalData['number']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->seeNumberOfElements("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a", 2);
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a.downloadPdf");
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a.previewPdf");

        $downloadPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a.downloadPdf", 'href');
        $previewPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5) a.previewPdf", 'href');

        $I->assertEquals("/administrator/index.php?option=com_mothership&task=proposal.downloadPdf&id={$proposalData['id']}", $downloadPdfUrl);
        $I->assertEquals("/administrator/index.php?option=com_mothership&task=proposal.previewPdf&id={$proposalData['id']}", $previewPdfUrl);

        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(6)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(7)");
        $I->see("{$proposalData['total']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(9)");
        $I->see(date('Y-m-d'), "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(11)");

        $I->see("1 - 2 / 2 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group proposal
     * @group delete
     * @group backend-proposal
     */
    public function MothershipDeleteProposalSuccess(AcceptanceTester $I)
    {
        $I->setProposalStatus($this->proposalData['id'], 1);
        $I->seeInDatabase('jos_mothership_proposals', ['id' => $this->proposalData['id']]);

        $I->amOnPage(self::PROPOSALS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Proposals", 30, "h1.page-title");

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "proposals.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "proposal.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "proposals.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::PROPOSALS_VIEW_ALL_URL);
        $I->see("Mothership: Proposals", "h1.page-title");
        $I->see("1 Proposal deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 0);

        $I->dontSeeInDatabase('jos_mothership_proposals', ['id' => $this->proposalData['id']]);
    }

    /**
     * @group backend
     * @group proposal
     * @group backend-proposal
     */
    public function MothershipAddProposal(AcceptanceTester $I)
    {
        $I->amOnPage(self::PROPOSALS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Proposals", 30, "h1.page-title");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");
        $I->click("{$toolbar} {$toolbarNew} .btn.button-new");

        $I->waitForText("Mothership: New Proposal", 30, "h1.page-title");
        $I->wait(5);

        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        $I->seeElement("select#jform_client_id");
        $I->dontSeeElement("select#jform_account_id");
        $I->dontSeeElement("select#jform_project_id");
        $I->seeElement("input#jform_number");
        $I->seeElement("input#jform_created");
        $I->seeElement("input#jform_rate");
        $I->seeElement("input#jform_total_low");
        $I->seeElement("input#jform_total");

        // Attempt to save the form without filling out any fields
        $I->click("Save", "#toolbar");
        $I->wait(5);

        // Check the form validation
        $I->see("The form cannot be submitted as it's missing required data.");
        $I->see("Please correct the marked fields and try again.");
        
        $I->see("One of the options must be selected", "label#jform_client_id-lbl .form-control-feedback");
        $I->see("Please fill in this field", "label#jform_number-lbl .form-control-feedback");
        $I->see("Please provide an item name.", ".form-group .invalid-feedback");

        $I->amGoingTo("Fill out the form");

        $I->selectOption("select#jform_client_id", $this->clientData['id']);
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_client_id", "{$this->clientData['name']}");
        $I->selectOption("select#jform_account_id", $this->accountData['id']);
        $I->dontSeeElement("select#jform_project_id");
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_account_id", "{$this->accountData['name']}");
        $I->wait(5);
        $I->selectOption("select#jform_project_id", "{$this->projectData['id']}");
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_project_id", "{$this->projectData['name']}");

        $I->fillFIeld("input#jform_number", "1001");
        $I->seeInField("input#jform_rate", $this->clientData['default_rate']);

        $I->fillFIeld("input#jform_total", "105.00");

        $I->amGoingTo("Fill out the first row of the proposal items table");
        $I->fillField("#proposal-items-table input[name='jform[items][0][name]']", "Test Item");
        $I->fillField("#proposal-items-table input[name='jform[items][0][description]']", "Test Description");
        $I->selectOption("#proposal-items-table select[name='jform[items][0][type]']", "Hourly");
        $I->fillField("#proposal-items-table input[name='jform[items][0][time_low]']", "01:00");
        $I->fillField("#proposal-items-table input[name='jform[items][0][time]']", "02:00");
        

        $I->seeInField("#proposal-items-table input[name='jform[items][0][quantity_low]']", "1.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][quantity]']", "2.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][rate]']", $this->clientData['default_rate']);
        $expectedSubtotalLow = number_format(($this->clientData['default_rate'] * 1), 2); 
        $expectedSubtotal = number_format(($this->clientData['default_rate'] * 2), 2);
        $I->seeInField("#proposal-items-table input[name='jform[items][0][subtotal_low]']", $expectedSubtotalLow);
        $I->seeInField("#proposal-items-table input[name='jform[items][0][subtotal]']", $expectedSubtotal);

        // Delete whats in quantity
        $I->executeJS("document.querySelector(\"#proposal-items-table input[name='jform[items][0][quantity]']\").value = '';");
        $I->fillField("#proposal-items-table input[name='jform[items][0][quantity]']", "2.00");

        $I->seeInField("#proposal-items-table input[name='jform[items][0][quantity]']", "2.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][rate]']", $this->clientData['default_rate']);
        $expectedSubtotal = number_format(($this->clientData['default_rate'] * 2), 2);
        $I->seeInField("#proposal-items-table input[name='jform[items][0][subtotal]']", $expectedSubtotal);

        $I->executeJS("document.querySelector(\"#proposal-items-table input[name='jform[items][0][rate]']\").value = '';");
        $I->fillField("table tbody tr:first-child input[name='jform[items][0][rate]']", "70.00");

        $I->click("#proposal-items-table input[name='jform[items][0][subtotal]']");

        $I->seeInField("#proposal-items-table input[name='jform[items][0][quantity]']", "2.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][rate]']", "70.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][subtotal]']", "140.00");

        $I->seeInField("input#jform_total", "140.00");

        $I->executeJS("document.querySelector('#add-proposal-item').scrollIntoView({ behavior: 'instant', block: 'center' });");
        $I->wait(1);
        $I->click("#add-proposal-item");

        $I->dontSee("#proposal-items-table input[name='jform[items][2][name]']");

        $I->fillField("#proposal-items-table input[name='jform[items][1][name]']", "A different Item");
        $I->fillField("#proposal-items-table input[name='jform[items][1][description]']", "Test Description");

        $I->fillField("#proposal-items-table input[name='jform[items][1][time_low]']", "01:30");
        $I->fillField("#proposal-items-table input[name='jform[items][1][time]']", "03:00");    

        $I->seeInField("#proposal-items-table input[name='jform[items][1][quantity_low]']", "1.50");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][quantity]']", "3.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][rate]']", "");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][subtotal_low]']", "0.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][subtotal]']", "0.00");

        $I->fillField("#proposal-items-table input[name='jform[items][1][time_low]']", "3:00");
        $I->fillField("#proposal-items-table input[name='jform[items][1][time]']", "06:00");    

        $I->seeInField("#proposal-items-table input[name='jform[items][1][quantity_low]']", "3.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][quantity]']", "6.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][rate]']", "");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][subtotal_low]']", "0.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][subtotal]']", "0.00");

        $I->executeJS("document.querySelector(\"#proposal-items-table input[name='jform[items][1][quantity]']\").value = '';");
        $I->fillField("#proposal-items-table input[name='jform[items][1][quantity]']", "3.75");

        $I->seeInField("#proposal-items-table input[name='jform[items][1][quantity]']", "3.75");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][rate]']", "");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][subtotal]']", "0.00");

        $I->executeJS("document.querySelector(\"#proposal-items-table input[name='jform[items][1][rate]']\").value = '';");
        $I->fillField("#proposal-items-table input[name='jform[items][1][rate]']", "70.00");
        $I->click("#proposal-items-table input[name='jform[items][1][subtotal]']");

        $I->seeInField("#proposal-items-table input[name='jform[items][1][quantity]']", "3.75");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][rate]']", "70.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][subtotal]']", "262.50");

        $I->fillField("input#jform_total", "402.50");

        $I->click("Save & Close", "#toolbar");
        $I->waitForText("Proposal saved successfully.", 10, "#system-message-container .alert-message");

        // Check that the new proposal has two rows of items
        $I->assertProposalHasRows(($this->proposalData['id'] + 1), 2);
       
        $I->waitForText("Mothership: Proposals", 30, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $I->see("1001", "#j-main-container table.itemList tbody tr td:nth-child(3)");
        $I->see("Test Client", "#j-main-container table.itemList tbody tr td:nth-child(6)");
        $I->see("Test Account", "#j-main-container table.itemList tbody tr td:nth-child(7)");
        $I->see(date("Y-m-d"), "#j-main-container table.itemList tbody tr td:nth-child(11)");

        // Open the Proposal again and confirm the data is correct
        $I->amOnPage(sprintf(self::PROPOSAL_EDIT_URL, ($this->proposalData['id'] + 1)));
        // Confirm the value in jform_number is correct
        $I->seeInField("input#jform_number", "1001");

        $I->click("Save", "#toolbar");
        $I->wait(2);

        // We should still be on the same edit page, with the same ID
        $I->seeInCurrentUrl(sprintf(self::PROPOSAL_EDIT_URL, ($this->proposalData['id'] + 1)));
        $I->see("Proposal saved successfully.", "#system-message-container .alert-message");

        // Check that the proposal displays the same data that was entered before
        $I->seeInField("input#jform_created", date('Y-m-d'));

        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");

        $I->seeInField("#proposal-items-table input[name='jform[items][0][name]']", "Test Item");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][description]']", "Test Description");
        $I->seeOptionIsSelected("#proposal-items-table select[name='jform[items][0][type]']", "Hourly");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][time_low]']", "01:00");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][time]']", "2:00");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][quantity]']", "2");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][rate]']", "70");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][subtotal]']", "140.00");
        // Now check the second row of items
        $I->seeInField("#proposal-items-table input[name='jform[items][1][name]']", "A different Item");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][description]']", "Test Description");

        $I->seeInField("#proposal-items-table input[name='jform[items][1][quantity]']", "3.75");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][rate]']", "70");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][subtotal]']", "262.50");

        $I->amOnPage(sprintf(self::PROPOSAL_EDIT_URL, ($this->proposalData['id'] + 1)));
        $I->wait(1);
        $I->waitForText("Mothership: Edit Proposal", 30, "h1.page-title");
        $I->click("Close", "#toolbar");
        $I->wait(1);
        $I->waitForText("Mothership: Proposals", 30, "h1.page-title");
        $I->seeInCurrentUrl(self::PROPOSALS_VIEW_ALL_URL);
        $I->dontSeeElement("span.icon-checkedout");
    }

    /**
     * @group backend
     * @group proposal
     * @group backend-proposal
     */
    public function MothershipEditInvalidProposal(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::PROPOSAL_EDIT_URL, 9999));
        $I->waitForElementVisible('#system-message-container', 30);
        $I->see('Proposal not found. Please select a valid proposal.', '#system-message-container');
        $I->see('Mothership: Proposals', 'h1.page-title');
        $I->seeInCurrentUrl(self::PROPOSALS_VIEW_ALL_URL);
    }  

    /**
     * @group backend
     * @group proposal
     * @group backend-proposal
     */
    public function proposalViewPdf(AcceptanceTester $I)
    {
        $I->amOnPage(self::PROPOSALS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Proposals", 30, "h1.page-title");

        $I->seeElement("#j-main-container table.itemList tbody tr:first-child a.downloadPdf");

        // I want to grab the html from the 4th child td element which has an a tag in it
        $html = $I->grabAttributeFrom("#j-main-container table.itemList tbody tr:first-child a.downloadPdf", 'href');
        codecept_debug($html);
        // Click on the 4th child td element which has an a tag in it
        $I->click("#j-main-container table.itemList tbody tr:first-child a.downloadPdf");
        $I->amOnPage($html);
        $I->wait(1);
        //$I->seeElement("embed[type='application/pdf']");
    }

    /**
     * @group backend
     * @group proposal
     * @group backend-proposal
     */
    public function proposalViewPdfTemplate(AcceptanceTester $I)
    {
        // $due_date = date('Y-m-d', strtotime('+30 days'));
        $proposalData = $I->createMothershipProposal([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'number' => 9000,
            'status' => 2,
            'total_low' => '111.00',
            'total' => '222.00',
        ]);

        $proposalItemData = [];
        $proposalItemData[] = $I->createMothershipProposalItem([
            'proposal_id' => $proposalData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'type' => 'hourly',
            'time_low' => "01:00",
            'time' => "02:00",
            'quantity_low' => 1.00,
            'quantity' => 2.00,
            'rate' => $this->clientData['default_rate'],
            'subtotal_low' => $this->clientData['default_rate'] * 1,
            'subtotal' => $this->clientData['default_rate'] * 2
        ]);

        $proposalItemData[] = $I->createMothershipProposalItem([
            'proposal_id' => $proposalData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'type' => 'fixed',
            'time_low' =>'',
            'time' => '',
            'quantity_low' => 0.00,
            'quantity' => 1.00,
            'rate' => 42.00,
            'subtotal_low' => 0.00,
            'subtotal' => 42.00 * 1
        ]);

        $I->amOnPage(self::PROPOSALS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Proposals", 30, "h1.page-title");

        $I->seeElement("#j-main-container table.itemList tbody tr:nth-child(2) a.previewPdf");

        // I want to grab the html from the 4th child td element which has an a tag in it
        $html = $I->grabAttributeFrom("#j-main-container table.itemList tbody tr:nth-child(2) a.previewPdf", 'href');
        codecept_debug($html);
        // Click on the 4th child td element which has an a tag in it
        $I->click("#j-main-container table.itemList tbody tr:nth-child(2) a.previewPdf");
        $I->amOnPage($html);
        $I->wait(1);
        
        // Check all the elements in the PDF
        $I->see("{$this->mothershipConfig['company_name']}");
        $I->see("{$this->mothershipConfig['company_address_1']}");
        $I->see("{$this->mothershipConfig['company_address_2']}");
        $I->see("{$this->mothershipConfig['company_city']}");
        $I->see("{$this->mothershipConfig['company_state']}");
        $I->see("{$this->mothershipConfig['company_zip']}");
        $I->see("{$this->mothershipConfig['company_phone']}");
        $I->see("{$this->mothershipConfig['company_email']}");
    
        // Check for the proposal meta data
        $I->see("Proposal of Services");
        $I->see("Proposal Number: #{$proposalData['number']}");
        // $I->see("Proposal Status: Opened");
        // $I->see("Proposal Due: {$due_date}");

        // Check the client data is displayed 
        $I->see("{$this->clientData['name']}");
        $I->see("{$this->clientData['address_1']}");
        $I->see("{$this->clientData['address_2']}");
        $I->see("{$this->clientData['city']}");
        $I->see("{$this->clientData['state']}");
        $I->see("{$this->clientData['zip']}");

        // Check the account name is displayed
        $I->see($this->accountData['name']);

        $I->see("ITEMS", "h2");

        $row = 0;
        $I->see("{$proposalItemData[$row]['name']}", "table tbody tr:nth-child(1) td:nth-child(1)");
        $I->see("{$proposalItemData[$row]['description']}", "table tbody tr:nth-child(1) td:nth-child(1)");
        $I->see("{$proposalItemData[$row]['time_low']}", "table tbody tr:nth-child(1) td:nth-child(2)");
        $I->see("{$proposalItemData[$row]['time']}", "table tbody tr:nth-child(1) td:nth-child(2)");
        $I->see("{$proposalItemData[$row]['rate']}", "table tbody tr:nth-child(1) td:nth-child(3)");
        $I->see("{$proposalItemData[$row]['subtotal_low']}", "table tbody tr:nth-child(1) td:nth-child(4)");
        $I->see("{$proposalItemData[$row]['subtotal']}", "table tbody tr:nth-child(1) td:nth-child(4)");
        $row++;
        $I->see("{$proposalItemData[$row]['name']}", "table tbody tr:nth-child(2) td:nth-child(1)");
        $I->see("{$proposalItemData[$row]['description']}", "table tbody tr:nth-child(2) td:nth-child(1)");
        $I->see("{$proposalItemData[$row]['rate']}", "table tbody tr:nth-child(2) td:nth-child(3)");
        $I->see("{$proposalItemData[$row]['subtotal']}", "table tbody tr:nth-child(2) td:nth-child(4)");    

        $I->see("Total: $222.00", ".totals h3");
    }
}