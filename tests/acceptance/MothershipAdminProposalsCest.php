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
            'hours' => '1',
            'minutes' => '30',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);

        $this->proposalItemData[] = $I->createMothershipProposalItem([
            'proposal_id' => $this->proposalData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'hours' => '1',
            'minutes' => '0',
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

        $proposalItemData[] = $I->createMothershipProposalItem([
            'proposal_id' => $proposalData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'hours' => '1',
            'minutes' => '30',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);

        $proposalItemData[] = $I->createMothershipProposalItem([
            'proposal_id' => $proposalData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'hours' => '1',
            'minutes' => '0',
            'quantity' => '1',
            'rate' => '70.00',
            'subtotal' => '70.00',
        ]);
        
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
        $I->see("PDF", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Client", "#j-main-container table thead tr th:nth-child(5)");
        $I->see("Account", "#j-main-container table thead tr th:nth-child(6)");
        $I->see("Project", "#j-main-container table thead tr th:nth-child(7)");
        $I->see("Total", "#j-main-container table thead tr th:nth-child(8)");
        $I->see("Status", "#j-main-container table thead tr th:nth-child(9)");
        $I->see("Payment Status", "#j-main-container table thead tr th:nth-child(10)");
        $I->see("Due", "#j-main-container table thead tr th:nth-child(11)");
        $I->see("Created", "#j-main-container table thead tr th:nth-child(12)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $row = 1;
        // This proposal is not locked, so it should not have the lock icon
        $I->dontSeeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(1) i.fa-solid.fa-lock");
        $I->see("{$this->proposalData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(2)");
        $I->see("{$this->proposalData['number']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->seeNumberOfElements("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a", 2);
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf");
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf");

        $downloadPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf", 'href');
        $previewPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf", 'href');

        $I->assertEquals("/administrator/index.php?option=com_mothership&task=proposal.downloadPdf&id={$this->proposalData['id']}", $downloadPdfUrl);
        $I->assertEquals("/administrator/index.php?option=com_mothership&task=proposal.previewPdf&id={$this->proposalData['id']}", $previewPdfUrl);

        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(6)");
        $I->see("{$this->projectData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(7)");
        $I->see("{$this->proposalData['total']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(8)");
        $I->see("Draft", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(9)");
        $I->see("Unpaid", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(10)");
        $I->see(date('Y-m-d'), "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(12)");

        $row = 2;
        // This proposal IS not locked, so it SHOULD have the lock icon
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(1) i.fa-solid.fa-lock");
        $I->see("{$proposalData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(2)");
        $I->see("{$proposalData['number']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->seeNumberOfElements("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a", 2);
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf");
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf");

        $downloadPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf", 'href');
        $previewPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf", 'href');

        $I->assertEquals("/administrator/index.php?option=com_mothership&task=proposal.downloadPdf&id={$proposalData['id']}", $downloadPdfUrl);
        $I->assertEquals("/administrator/index.php?option=com_mothership&task=proposal.previewPdf&id={$proposalData['id']}", $previewPdfUrl);

        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(6)");
        $I->see("{$proposalData['total']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(8)");
        $I->see("Closed", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(9)");
        $I->see("Paid", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(10)");
        $I->see("Payment #{$paymentData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(10)"); 
        $I->see(date('Y-m-d'), "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(12)");

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

        $toolbar           = "#toolbar";
        $toolbarNew        = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";

        // Basic toolbar sanity
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");
        $I->click("{$toolbar} {$toolbarNew} .btn.button-new");
        $I->waitForText("Mothership: New Proposal", 30, "h1.page-title");

        // Toolbar buttons
        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        // Core proposal fields (from proposal.xml)
        $I->seeElement("select#jform_client_id");
        $I->dontSeeElement("select#jform_account_id"); // should appear after client selection
        $I->dontSeeElement("select#jform_project_id"); // should appear after account selection

        $I->seeElement("select#jform_type");
        $I->seeElement("input#jform_number");
        $I->seeElement("input#jform_total_low");
        $I->seeElement("input#jform_total");
        $I->seeElement("input#jform_rate");
        $I->seeElement("select#jform_status");
        $I->seeElement("input#jform_due_date");
        $I->seeElement("input#jform_created");

        // Try saving with nothing filled to trigger validation
        $I->click("Save", "#toolbar");
        $I->wait(1);

        // Global validation messages
        $I->see("The form cannot be submitted as it's missing required data.");
        $I->see("Please correct the marked fields and try again.");

        // Client required
        $I->see("One of the options must be selected", "label#jform_client_id-lbl .form-control-feedback");
        // Number required
        $I->see("Please fill in this field", "label#jform_number-lbl .form-control-feedback");
        // Items required (custom message from items field)
        $I->see("Please provide an item name.", ".form-group .invalid-feedback");

        $I->amGoingTo("Fill out the proposal header and items");

        // ---- Header: client/account/project ----
        $I->selectOption("select#jform_client_id", $this->clientData['id']);
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_client_id", "{$this->clientData['name']}");

        // Account list should now be visible
        $I->waitForElementVisible("select#jform_account_id", 10);
        $I->selectOption("select#jform_account_id", $this->accountData['id']);
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_account_id", "{$this->accountData['name']}");

        // Project should appear after account selection
        $I->waitForElementVisible("select#jform_project_id", 10);
        $I->selectOption("select#jform_project_id", "{$this->projectData['id']}");
        $I->wait(1);
        $I->seeOptionIsSelected("select#jform_project_id", "{$this->projectData['name']}");

        // Proposal type (overall) – expect default hourly
        $I->seeOptionIsSelected("select#jform_type", "hourly");

        // Proposal number
        $I->fillField("input#jform_number", "1001");

        // Rate should default from client
        $I->seeInField("input#jform_rate", $this->clientData['default_rate']);
        $rate = (float) $this->clientData['default_rate'];

        // ------------------------------------------------------------------
        // First item row (hourly) – uses quantity_low / high / rate
        // ------------------------------------------------------------------
        $I->amGoingTo("Fill out the first proposal item row as hourly");

        $I->fillField("#proposal-items-table input[name='jform[items][0][name]']", "Test Item");
        $I->fillField("#proposal-items-table input[name='jform[items][0][description]']", "Test Description");

        // Item type: hourly
        $I->selectOption("#proposal-items-table select[name='jform[items][0][type]']", "hourly");

        // Low and High quantities
        $I->fillField("#proposal-items-table input[name='jform[items][0][quantity_low]']", "1");
        $I->fillField("#proposal-items-table input[name='jform[items][0][high]']", "2");

        // Rate should match proposal rate by default
        $I->seeInField("#proposal-items-table input[name='jform[items][0][rate]']", $this->clientData['default_rate']);

        // Trigger JS recalculation (click into one of the total fields)
        $I->click("#proposal-items-table input[name='jform[items][0][low-total]']");
        $I->wait(1);

        $expectedLow1  = number_format($rate * 1, 2);
        $expectedHigh1 = number_format($rate * 2, 2);

        $I->seeInField("#proposal-items-table input[name='jform[items][0][low-total]']", $expectedLow1);
        $I->seeInField("#proposal-items-table input[name='jform[items][0][high-total]']", $expectedHigh1);

        // Proposal header totals should reflect the first row
        $I->seeInField("input#jform_total_low", $expectedLow1);
        $I->seeInField("input#jform_total", $expectedHigh1);

        // ------------------------------------------------------------------
        // Second item row (fixed) – different type and rate
        // ------------------------------------------------------------------
        $I->amGoingTo("Add a second fixed-price proposal item row");

        $I->executeJS("document.querySelector('#add-proposal-item').scrollIntoView({ behavior: 'instant', block: 'center' });");
        $I->wait(1);
        $I->click("#add-proposal-item");
        $I->wait(1);

        // Ensure we did not accidentally create a third row yet
        $I->dontSee("#proposal-items-table input[name='jform[items][2][name]']");

        $I->fillField("#proposal-items-table input[name='jform[items][1][name]']", "A different Item");
        $I->fillField("#proposal-items-table input[name='jform[items][1][description]']", "Test Description");

        // Type fixed
        $I->selectOption("#proposal-items-table select[name='jform[items][1][type]']", "fixed");

        // Low/high quantities (same for fixed)
        $I->fillField("#proposal-items-table input[name='jform[items][1][quantity_low]']", "3");
        $I->fillField("#proposal-items-table input[name='jform[items][1][high]']", "3");

        // Explicit rate for this line
        $I->fillField("#proposal-items-table input[name='jform[items][1][rate]']", "50.00");

        // Trigger recalculation
        $I->click("#proposal-items-table input[name='jform[items][1][low-total]']");
        $I->wait(1);

        $expectedLow2  = number_format(3 * 50, 2); // 150.00
        $expectedHigh2 = $expectedLow2;

        $I->seeInField("#proposal-items-table input[name='jform[items][1][low-total]']", $expectedLow2);
        $I->seeInField("#proposal-items-table input[name='jform[items][1][high-total]']", $expectedHigh2);

        // Combined header totals = sum of row totals
        $combinedLow  = number_format((float)$expectedLow1 + (float)$expectedLow2, 2);
        $combinedHigh = number_format((float)$expectedHigh1 + (float)$expectedHigh2, 2);

        $I->seeInField("input#jform_total_low", $combinedLow);
        $I->seeInField("input#jform_total", $combinedHigh);

        // ------------------------------------------------------------------
        // Save & Close and verify DB + list view
        // ------------------------------------------------------------------
        $I->click("Save & Close", "#toolbar");
        $I->waitForText("Proposal saved successfully.", 5, "#system-message-container .alert-message");
        $I->waitForText("Mothership: Proposals", 30, "h1.page-title");

        // There should now be at least one proposal row. For a clean DB seed, often 2:
        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $I->see("1001", "#j-main-container table.itemList tbody tr td:nth-child(3)");
        $I->see("Test Client", "#j-main-container table.itemList tbody tr td:nth-child(5)");
        $I->see("Test Account", "#j-main-container table.itemList tbody tr td:nth-child(6)");
        $I->see(date("Y-m-d"), "#j-main-container table.itemList tbody tr td:nth-child(12)");

        // If your seed creates a previous proposal, the new one is usually +1
        $newProposalId = $this->proposalData['id'] + 1;

        // Check that the new proposal has two rows of items in storage
        // NOTE: adjust keys here to match however you persist the JSON
        $I->assertProposalHasRows($newProposalId, 2);
        $I->assertProposalHasItems($newProposalId, [
            [
                'name'         => 'Test Item',
                'description'  => 'Test Description',
                'type'         => 'hourly',
                'quantity_low' => 1,
                'high'         => 2,
                'rate'         => (float) $rate,
                'low-total'    => (float) $expectedLow1,
                'high-total'   => (float) $expectedHigh1,
            ],
            [
                'name'         => 'A different Item',
                'description'  => 'Test Description',
                'type'         => 'fixed',
                'quantity_low' => 3,
                'high'         => 3,
                'rate'         => 50.0,
                'low-total'    => (float) $expectedLow2,
                'high-total'   => (float) $expectedHigh2,
            ],
        ]);

        // ------------------------------------------------------------------
        // Re-open the proposal and confirm persistence
        // ------------------------------------------------------------------
        $I->amOnPage(sprintf(self::PROPOSAL_EDIT_URL, $newProposalId));
        $I->waitForText("Mothership: Edit Proposal", 30, "h1.page-title");

        $I->seeInField("input#jform_number", "1001");
        $I->seeInField("input#jform_created", date('Y-m-d'));
        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");
        $I->seeOptionIsSelected("select#jform_type", "hourly");

        $I->seeInField("#proposal-items-table input[name='jform[items][0][name]']", "Test Item");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][description]']", "Test Description");
        $I->seeOptionIsSelected("#proposal-items-table select[name='jform[items][0][type]']", "hourly");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][quantity_low]']", "1");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][high]']", "2");
        $I->seeInField("#proposal-items-table input[name='jform[items][0][rate]']", number_format($rate, 2));
        $I->seeInField("#proposal-items-table input[name='jform[items][0][low-total]']", $expectedLow1);
        $I->seeInField("#proposal-items-table input[name='jform[items][0][high-total]']", $expectedHigh1);

        $I->seeInField("#proposal-items-table input[name='jform[items][1][name]']", "A different Item");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][description]']", "Test Description");
        $I->seeOptionIsSelected("#proposal-items-table select[name='jform[items][1][type]']", "fixed");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][quantity_low]']", "3");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][high]']", "3");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][rate]']", "50.00");
        $I->seeInField("#proposal-items-table input[name='jform[items][1][low-total]']", $expectedLow2);
        $I->seeInField("#proposal-items-table input[name='jform[items][1][high-total]']", $expectedHigh2);

        $I->seeInField("input#jform_total_low", $combinedLow);
        $I->seeInField("input#jform_total", $combinedHigh);

        // Close back to list, ensure not checked out
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
        $I->amOnPage(sprintf(self::PROPOSAL_EDIT_URL, "9999"));
        $I->wait(1);
        $I->waitForText('Mothership: Proposals', 30, 'h1.page-title');
        $I->waitForText("Proposal not found. Please select a valid proposal.", 30, "#system-message-container .alert-message");
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
            'total' => '123.45',
           //  'due_date' => $due_date,
        ]);

        $proposalItemData = [];
        $proposalItemData[] = $I->createMothershipProposalItem([
            'proposal_id' => $proposalData['id'],
            'name' => 'Test Item',
            'description' => 'Test Description',
            'hours' => 1,
            'minutes' => 0,
            'quantity' => 1.00,
            'rate' => $this->clientData['default_rate'],
            'subtotal' => $this->clientData['default_rate'] * 1,
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
        $I->see("Proposal Status: Opened");
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

        $I->see("SERVICES RENDERED");
        $I->see("Hours");
        // $I->see("Minutes");
        // $I->see("Quantity");
        $I->see("Rate");
        $I->see("Subtotal");


        $I->see("{$proposalItemData[0]['name']}");
        // $I->see("{$this->proposalItemData[0]['description']}");
        $I->see("{$proposalItemData[0]['quantity']}");
        // $I->see("{$this->proposalItemData[0]['minutes']}");
        // $I->see("{$this->proposalItemData[0]['quantity']}");
        $I->see("{$proposalItemData[0]['rate']}");
        $I->see("{$proposalItemData[0]['subtotal']}");

    }
}