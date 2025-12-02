<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;


class MothershipAdminEstimatesCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $estimateData;
    private $projectData;
    private $estimateItemData = [];
    private $mothershipConfig = [];

    const ESTIMATES_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=estimates";
    const ESTIMATE_EDIT_URL = "/administrator/index.php?option=com_mothership&view=estimate&layout=edit&id=%s";
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

        $this->estimateData = $I->createMothershipEstimate([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => $this->projectData['id'],   
            'total' => '175.00',
            'number' => 1000,
            'due_date' => NULL,
            'created' => date('Y-m-d H:i:s'),
            'status' => 1,
        ]);

        $this->estimateItemData[] = $I->createMothershipEstimateItem([
            'estimate_id' => $this->estimateData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'hours' => '1',
            'minutes' => '30',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);

        $this->estimateItemData[] = $I->createMothershipEstimateItem([
            'estimate_id' => $this->estimateData['id'],
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
     * @group estimate
     * @group backend-estimate
     */
    public function MothershipViewEstimates(AcceptanceTester $I)
    {

        $estimateData = $I->createMothershipEstimate([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => NULL,
            'total' => '475.00',
            'number' => 1004,
            'created' => date('Y-m-d H:i:s'),
            'status' => 4,
            'locked' => 1,
        ]);

        $estimateItemData[] = $I->createMothershipEstimateItem([
            'estimate_id' => $estimateData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'hours' => '1',
            'minutes' => '30',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);

        $estimateItemData[] = $I->createMothershipEstimateItem([
            'estimate_id' => $estimateData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'hours' => '1',
            'minutes' => '0',
            'quantity' => '1',
            'rate' => '70.00',
            'subtotal' => '70.00',
        ]);
        
        $paymentData = $I->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => $estimateData['total'],
            'payment_method' => 'paypal',
            'status' => 2,
            'locked' => 1,
        ]);

        $paymentEstimateData = $I->createMothershipEstimatePayment([
            'estimate_id' => $estimateData['id'],
            'payment_id' => $paymentData['id'],
            'applied_amount' => $estimateData['total'],
        ]);

        $I->amOnPage(self::ESTIMATES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Estimates", 30, "h1.page-title");

        $I->makeScreenshot("mothership-view-estimates");

        $I->dontSee("Warning");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");
        
        $I->see("ID", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Estimate Number", "#j-main-container table thead tr th:nth-child(3)");
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
        // This estimate is not locked, so it should not have the lock icon
        $I->dontSeeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(1) i.fa-solid.fa-lock");
        $I->see("{$this->estimateData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(2)");
        $I->see("{$this->estimateData['number']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->seeNumberOfElements("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a", 2);
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf");
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf");

        $downloadPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf", 'href');
        $previewPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf", 'href');

        $I->assertEquals("/administrator/index.php?option=com_mothership&task=estimate.downloadPdf&id={$this->estimateData['id']}", $downloadPdfUrl);
        $I->assertEquals("/administrator/index.php?option=com_mothership&task=estimate.previewPdf&id={$this->estimateData['id']}", $previewPdfUrl);

        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(6)");
        $I->see("{$this->projectData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(7)");
        $I->see("{$this->estimateData['total']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(8)");
        $I->see("Draft", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(9)");
        $I->see("Unpaid", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(10)");
        $I->see(date('Y-m-d'), "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(12)");

        $row = 2;
        // This estimate IS not locked, so it SHOULD have the lock icon
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(1) i.fa-solid.fa-lock");
        $I->see("{$estimateData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(2)");
        $I->see("{$estimateData['number']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(3)");
        $I->seeNumberOfElements("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a", 2);
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf");
        $I->seeElement("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf");

        $downloadPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.downloadPdf", 'href');
        $previewPdfUrl = $I->grabAttributeFrom("#j-main-container table tbody tr:nth-child({$row}) td:nth-child(4) a.previewPdf", 'href');

        $I->assertEquals("/administrator/index.php?option=com_mothership&task=estimate.downloadPdf&id={$estimateData['id']}", $downloadPdfUrl);
        $I->assertEquals("/administrator/index.php?option=com_mothership&task=estimate.previewPdf&id={$estimateData['id']}", $previewPdfUrl);

        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(5)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(6)");
        $I->see("{$estimateData['total']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(8)");
        $I->see("Closed", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(9)");
        $I->see("Paid", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(10)");
        $I->see("Payment #{$paymentData['id']}", "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(10)"); 
        $I->see(date('Y-m-d'), "#j-main-container table tbody tr:nth-child({$row}) td:nth-child(12)");

        $I->see("1 - 2 / 2 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group estimate
     * @group delete
     * @group backend-estimate
     */
    public function MothershipDeleteEstimateSuccess(AcceptanceTester $I)
    {
        $I->setEstimateStatus($this->estimateData['id'], 1);
        $I->seeInDatabase('jos_mothership_estimates', ['id' => $this->estimateData['id']]);

        $I->amOnPage(self::ESTIMATES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Estimates", 30, "h1.page-title");

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "estimates.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "estimate.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "estimates.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::ESTIMATES_VIEW_ALL_URL);
        $I->see("Mothership: Estimates", "h1.page-title");
        $I->see("1 Estimate deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 0);

        $I->dontSeeInDatabase('jos_mothership_estimates', ['id' => $this->estimateData['id']]);
    }

    /**
     * @group backend
     * @group estimate
     * @group backend-estimate
     */
    public function MothershipAddEstimate(AcceptanceTester $I)
    {
        $I->amOnPage(self::ESTIMATES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Estimates", 30, "h1.page-title");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");
        $I->click("{$toolbar} {$toolbarNew} .btn.button-new");
        $I->waitForText("Mothership: New Estimate", 30, "h1.page-title");
        $I->wait(5);

        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        $I->seeElement("select#jform_client_id");
        $I->dontSeeElement("select#jform_account_id");
        $I->dontSeeElement("select#jform_project_id");
        $I->seeElement("input#jform_number");
        $I->seeElement("input#jform_created");
        $I->seeElement("input#jform_due_date");
        $I->seeElement("input#jform_rate");
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

        $I->amGoingTo("Fill out the first row of the estimate items table");
        $I->fillField("#estimate-items-table input[name='jform[items][0][name]']", "Test Item");
        $I->fillField("#estimate-items-table input[name='jform[items][0][description]']", "Test Description");

        $I->fillField("#estimate-items-table input[name='jform[items][0][hours]']", "1");

        $I->seeInField("#estimate-items-table input[name='jform[items][0][hours]']", "1");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][minutes]']", "0");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][quantity]']", "1.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][rate]']", $this->clientData['default_rate']);
        $expectedSubtotal = number_format(($this->clientData['default_rate'] * 1), 2); // Update this value if needed based on calculations
        $I->seeInField("#estimate-items-table input[name='jform[items][0][subtotal]']", $expectedSubtotal);

        $I->fillField("#estimate-items-table input[name='jform[items][0][minutes]']", "30");

        $I->seeInField("#estimate-items-table input[name='jform[items][0][hours]']", "1");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][minutes]']", "30");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][quantity]']", "1.50");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][rate]']", $this->clientData['default_rate']);
        $expectedSubtotal = number_format(($this->clientData['default_rate'] * 1.5), 2); // Update this value if needed based on calculations
        $I->seeInField("#estimate-items-table input[name='jform[items][0][subtotal]']", $expectedSubtotal);

        // Delete whats in quantity
        $I->executeJS("document.querySelector(\"#estimate-items-table input[name='jform[items][0][quantity]']\").value = '';");
        $I->fillField("#estimate-items-table input[name='jform[items][0][quantity]']", "2.00");

        $I->seeInField("#estimate-items-table input[name='jform[items][0][hours]']", "2");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][minutes]']", "0");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][quantity]']", "2.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][rate]']", $this->clientData['default_rate']);
        $expectedSubtotal = number_format(($this->clientData['default_rate'] * 2), 2); // Update this value if needed based on calculations
        $I->seeInField("#estimate-items-table input[name='jform[items][0][subtotal]']", $expectedSubtotal);

        $I->executeJS("document.querySelector(\"#estimate-items-table input[name='jform[items][0][rate]']\").value = '';");
        $I->fillField("table tbody tr:first-child input[name='jform[items][0][rate]']", "70.00");

        $I->click("#estimate-items-table input[name='jform[items][0][subtotal]']");

        $I->seeInField("#estimate-items-table input[name='jform[items][0][hours]']", "2");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][minutes]']", "0");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][quantity]']", "2.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][rate]']", "70.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][subtotal]']", "140.00");

        $I->seeInField("input#jform_total", "140.00");

        $I->executeJS("document.querySelector('#add-estimate-item').scrollIntoView({ behavior: 'instant', block: 'center' });");
        $I->wait(1);
        $I->click("#add-estimate-item");

        $I->dontSee("#estimate-items-table input[name='jform[items][2][name]']");

        $I->fillField("#estimate-items-table input[name='jform[items][1][name]']", "A different Item");
        $I->fillField("#estimate-items-table input[name='jform[items][1][description]']", "Test Description");

        $I->fillField("#estimate-items-table input[name='jform[items][1][hours]']", "2");

        $I->seeInField("#estimate-items-table input[name='jform[items][1][hours]']", "2");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][minutes]']", "0");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][quantity]']", "2.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][rate]']", "0.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][subtotal]']", "0.00");

        $I->fillField("#estimate-items-table input[name='jform[items][1][minutes]']", "45");

        $I->seeInField("#estimate-items-table input[name='jform[items][1][hours]']", "2");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][minutes]']", "45");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][quantity]']", "2.75");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][rate]']", "0.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][subtotal]']", "0.00");

        $I->executeJS("document.querySelector(\"#estimate-items-table input[name='jform[items][1][quantity]']\").value = '';");
        $I->fillField("#estimate-items-table input[name='jform[items][1][quantity]']", "3.75");

        $I->seeInField("#estimate-items-table input[name='jform[items][1][hours]']", "3");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][minutes]']", "45");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][quantity]']", "3.75");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][rate]']", "0.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][subtotal]']", "0.00");

        $I->executeJS("document.querySelector(\"#estimate-items-table input[name='jform[items][1][rate]']\").value = '';");
        $I->fillField("#estimate-items-table input[name='jform[items][1][rate]']", "70.00");
        $I->click("#estimate-items-table input[name='jform[items][1][subtotal]']");

        $I->seeInField("#estimate-items-table input[name='jform[items][1][hours]']", "3");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][minutes]']", "45");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][quantity]']", "3.75");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][rate]']", "70.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][subtotal]']", "262.50");

        $I->fillField("input#jform_total", "402.50");

        $I->click("Save & Close", "#toolbar");
        $I->waitForText("Estimate saved successfully.", 5, "#system-message-container .alert-message");

        // Check that the new estimate has two rows of items
        $I->assertEstimateHasRows(($this->estimateData['id'] + 1), 2);
        $I->assertEstimateHasItems($this->estimateData['id'] + 1, [
            ['name' => 'Test Item', 'description' => 'Test Description', 'hours' => 2, 'minutes' => 0, 'quantity' => 2.00, 'rate' => 70.0, 'subtotal' => 140.00],
            ['name' => 'A different Item', 'description' => 'Test Description', 'hours' => 3, 'minutes' => 45, 'quantity' => 3.75, 'rate' => 70.0, 'subtotal' => 262.50],
        ]);

        $I->waitForText("Mothership: Estimates", 30, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $I->see("1001", "#j-main-container table.itemList tbody tr td:nth-child(3)");
        $I->see("Test Client", "#j-main-container table.itemList tbody tr td:nth-child(5)");
        $I->see("Test Account", "#j-main-container table.itemList tbody tr td:nth-child(6)");
        $I->see(date("Y-m-d"), "#j-main-container table.itemList tbody tr td:nth-child(12)");

        // Open the Estimate again and confirm the data is correct
        $I->amOnPage(sprintf(self::ESTIMATE_EDIT_URL, ($this->estimateData['id'] + 1)));
        // Confirm the value in jform_number is correct
        $I->seeInField("input#jform_number", "1001");

        $I->click("Save", "#toolbar");
        $I->wait(2);

        // We should still be on the same edit page, with the same ID
        $I->seeInCurrentUrl(sprintf(self::ESTIMATE_EDIT_URL, ($this->estimateData['id'] + 1)));
        $I->see("Estimate saved successfully.", "#system-message-container .alert-message");

        // Check that the estimate displays the same data that was entered before
        $I->seeInField("input#jform_created", date('Y-m-d'));

        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");

        $I->seeInField("#estimate-items-table input[name='jform[items][0][name]']", "Test Item");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][description]']", "Test Description");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][hours]']", "2");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][minutes]']", "0");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][quantity]']", "2");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][rate]']", "70.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][0][subtotal]']", "140.00");
        // Now check the second row of items
        $I->seeInField("#estimate-items-table input[name='jform[items][1][name]']", "A different Item");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][description]']", "Test Description");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][hours]']", "3");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][minutes]']", "45");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][quantity]']", "3.75");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][rate]']", "70.00");
        $I->seeInField("#estimate-items-table input[name='jform[items][1][subtotal]']", "262.50");

        $I->amOnPage(sprintf(self::ESTIMATE_EDIT_URL, ($this->estimateData['id'] + 1)));
        $I->wait(1);
        $I->waitForText("Mothership: Edit Estimate", 30, "h1.page-title");
        $I->click("Close", "#toolbar");
        $I->wait(1);
        $I->waitForText("Mothership: Estimates", 30, "h1.page-title");
        $I->seeInCurrentUrl(self::ESTIMATES_VIEW_ALL_URL);
        $I->dontSeeElement("span.icon-checkedout");
    }

    /**
     * @group backend
     * @group estimate
     * @group backend-estimate
     */
    public function MothershipEditInvalidEstimate(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::ESTIMATE_EDIT_URL, "9999"));
        $I->wait(1);
        $I->waitForText('Mothership: Estimates', 30, 'h1.page-title');
        $I->waitForText("Estimate not found. Please select a valid estimate.", 30, "#system-message-container .alert-message");
        $I->seeInCurrentUrl(self::ESTIMATES_VIEW_ALL_URL);
    }  

    /**
     * @group backend
     * @group estimate
     * @group backend-estimate
     */
    public function estimateViewPdf(AcceptanceTester $I)
    {
        $I->amOnPage(self::ESTIMATES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Estimates", 30, "h1.page-title");

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
     * @group estimate
     * @group backend-estimate
     */
    public function estimateViewPdfTemplate(AcceptanceTester $I)
    {
        $due_date = date('Y-m-d', strtotime('+30 days'));
        $estimateData = $I->createMothershipEstimate([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'number' => 9000,
            'status' => 2,
            'total' => '123.45',
            'due_date' => $due_date,
        ]);

        $estimateItemData = [];
        $estimateItemData[] = $I->createMothershipEstimateItem([
            'estimate_id' => $estimateData['id'],
            'name' => 'Test Item',
            'description' => 'Test Description',
            'hours' => 1,
            'minutes' => 0,
            'quantity' => 1.00,
            'rate' => $this->clientData['default_rate'],
            'subtotal' => $this->clientData['default_rate'] * 1,
        ]);

        $I->amOnPage(self::ESTIMATES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Estimates", 30, "h1.page-title");

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
    
        // Check for the estimate meta data
        $I->see("Estimate of Services");
        $I->see("Estimate Number: #{$estimateData['number']}");
        $I->see("Estimate Status: Opened");
        $I->see("Estimate Due: {$due_date}");

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


        $I->see("{$estimateItemData[0]['name']}");
        // $I->see("{$this->estimateItemData[0]['description']}");
        $I->see("{$estimateItemData[0]['quantity']}");
        // $I->see("{$this->estimateItemData[0]['minutes']}");
        // $I->see("{$this->estimateItemData[0]['quantity']}");
        $I->see("{$estimateItemData[0]['rate']}");
        $I->see("{$estimateItemData[0]['subtotal']}");

    }
}