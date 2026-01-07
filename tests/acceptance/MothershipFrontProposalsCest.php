<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipFrontProposalsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $proposalData;
    private $mothershipConfig;
    private $joomlaUserData;
    private $proposalItemData = [];

    const PROPOSALS_VIEW_ALL_URL = "index.php?option=com_mothership&view=proposals";
    const PROPOSALS_VIEW_ALL_SEF_URL = "/account-center/billing/proposals/";

    const PROPOSAL_VIEW_URL = "index.php?option=com_mothership&view=proposal&layout=default&id=%s";
    const PROPOSAL_VIEW_SEF_URL = "/account-center/billing/proposals/%s/";

    const PROPOSAL_VIEW_PDF_URL = "index.php?option=com_mothership&view=proposal&controller=proposal&id=%s&task=viewPdf";
    const PROPOSAL_VIEW_PDF_SEF_URL = "/account-center/billing/proposals/%s/viewPdf/";

    const PROPOSAL_PAY_URL = "index.php?option=com_mothership&task=proposal.payment&id=%s";
    const PROPOSAL_PAY_SEF_URL = "/account-center/billing/proposals/%s/pay/";

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

        $this->joomlaUserData = $I->createJoomlaUser([], 10);

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

        $this->proposalData = $I->createMothershipProposal([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '177.50',
            'total_low' => '222.00',
            'number' => '1000',
            'status' => 2,
        ]);

        $this->proposalItemData = [];
        $this->proposalItemData[] = $I->createMothershipProposalItem(array(
            'proposal_id'   => $this->proposalData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'time_low' => '01:00',
            'time' => '01:30',
            'quantity_low'  => 1,
            'quantity' => 1.5,
            'rate' => 111,
            'subtotal_low'  => 111,
            'subtotal'      => 166.5,
        ));

        $this->proposalItemData[] = $I->createMothershipProposalItem(array(
            'proposal_id'   => $this->proposalData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'time_low' => '01:00',
            'time' => '01:00',
            'quantity_low'  => 1,
            'quantity' => 1,
            'rate' => 111,
            'subtotal_low'  => 111,
            'subtotal' => 111,
        ));

        $I->amOnPage("/");
        $I->fillField(".mod-login input[name=username]", strtolower($this->joomlaUserData['username']));
        $I->fillField(".mod-login input[name=password]", '4&GoH#7FvPsY');
        $I->click(".mod-login button[type=submit]");
        $I->wait(1);
        $I->see("Hi {$this->joomlaUserData['name']},");
    }

    /**
     * @group frontend
     * @group proposal
     */
    public function ViewAllProposalsPage(AcceptanceTester $I)
    {
        // Verify redirection to account center
        $I->amOnPage(self::PROPOSALS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Proposals", 10, "h1");

        $I->takeFullPageScreenshot("account-center-view-all-proposals");
        $I->dontSee("Warning:");

        // Confirm the correct number of records
        $I->seeNumberOfElements("table#proposalsTable tbody tr", 1);

        $I->see("Proposal Status Legend", ".mt-4 .col-md-6:nth-child(1)");
        $I->see("Pending", ".mt-4 .col-md-6:nth-child(1) ul.mb-0 li:nth-child(1)");
        $I->see("Approved", ".mt-4 .col-md-6:nth-child(1) ul.mb-0 li:nth-child(2)");
        $I->see("Declined", ".mt-4 .col-md-6:nth-child(1) ul.mb-0 li:nth-child(3)");
        $I->see("Canceled", ".mt-4 .col-md-6:nth-child(1) ul.mb-0 li:nth-child(4)");
        $I->see("Expired", ".mt-4 .col-md-6:nth-child(1) ul.mb-0 li:nth-child(5)");
        $I->seeNumberOfElements(".mt-4 .col-md-6:nth-child(1) ul.mb-0 li", 5);

        // Confirm the table headers
        $I->see("PDF", "table#proposalsTable thead tr th:nth-child(1)");
        $I->see("#", "table#proposalsTable thead tr th:nth-child(2)");
        $I->see("Name", "table#proposalsTable thead tr th:nth-child(3)");
        $I->see("Client", "table#proposalsTable thead tr th:nth-child(4)");
        $I->see("Account", "table#proposalsTable thead tr th:nth-child(5)");
        $I->see("Amount", "table#proposalsTable thead tr th:nth-child(6)");
        $I->see("Status", "table#proposalsTable thead tr th:nth-child(7)");
        $I->see("Actions", "table#proposalsTable thead tr th:nth-child(8)");

        // Confirm the table data
        $I->see($this->proposalData['number'], "table#proposalsTable tbody tr td:nth-child(2)");
        $I->see($this->proposalData['name'], "table#proposalsTable tbody tr td:nth-child(3)");
        $I->see($this->clientData['name'], "table#proposalsTable tbody tr td:nth-child(4)");
        $I->see($this->accountData['name'], "table#proposalsTable tbody tr td:nth-child(5)");
        $I->see("$177.50", "table#proposalsTable tbody tr td:nth-child(6)");
        $I->see("Pending", "table#proposalsTable tbody tr td:nth-child(7)");
        $I->see("View", "table#proposalsTable tbody tr td:nth-child(8) ul li");
        
        $I->click("Approve");
        $I->wait(1);
        $I->waitForText("Approve Proposal", 10, "h1");
    }

    /**
     * @group frontend
     * @group proposal
     */
    public function ViewProposalPage(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::PROPOSAL_VIEW_URL, $this->proposalData['id']));
        $log_created = date('Y-m-d H:i:s');
        $I->wait(1);
        $I->waitForText("Proposal of Services", 10, "h1");
        $I->dontSee("Warning:");

        $I->takeFullPageScreenshot("account-center-view-proposal");
                
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
        $I->see("Proposal Number: #{$this->proposalData['number']}");
        // $I->see("Proposal Status: Pending");
        // $I->see("Proposal Due:");

        // Check the client data is displayed 
        $I->see("{$this->clientData['name']}");
        $I->see("{$this->clientData['address_1']}");
        $I->see("{$this->clientData['address_2']}");
        $I->see("{$this->clientData['city']}");
        $I->see("{$this->clientData['state']}");
        $I->see("{$this->clientData['zip']}");

        // Check the account name is displayed
        $I->see($this->accountData['name']);

        $I->see("ITEMS ");
        $I->see("Range");
        $I->see("Rate");
        $I->see("Subtotal");

        $I->see("{$this->proposalItemData[0]['name']}");
        $I->see("{$this->proposalItemData[0]['description']}");
        $I->see("{$this->proposalItemData[0]['time_low']} - {$this->proposalItemData[0]['time']}");
        $I->see("{$this->proposalItemData[0]['rate']}");
        $I->see("{$this->proposalItemData[0]['subtotal']}");

        $I->see("{$this->proposalItemData[1]['name']}");
        $I->see("{$this->proposalItemData[1]['description']}");
        $I->see("{$this->proposalItemData[1]['time_low']} - {$this->proposalItemData[1]['time']}");
        $I->see("{$this->proposalItemData[1]['rate']}");
        $I->see("{$this->proposalItemData[1]['subtotal']}");

        /*
        $created = $I->grabFromDatabase("jos_mothership_logs", "created", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'user_id' => $this->joomlaUserData['id'],            
            'action' => 'viewed',
            'object_type' => 'proposal',
            'object_id' => $this->accountData['id'],
        ]);
        */

        //$timeDifference = abs(strtotime($log_created) - strtotime($created));
        // $I->assertLessThanOrEqual(2, $timeDifference, "Log created date should not differ by more than 2 seconds.");
        
    }

    /**
     * @group frontend
     * @group proposal
     */
    public function ProposalPageViewPdf(AcceptanceTester $I)
    {
        $I->amOnPage(self::PROPOSALS_VIEW_ALL_URL);
        $I->waitForText("Proposals", 10, "h1");
        $I->dontSee("Warning:");

        $I->see("PDF", "table#proposalsTable tbody tr:first-child td:nth-child(1)");
        $I->click("PDF", "table#proposalsTable tbody tr:first-child td:nth-child(1)");
        // How do I switch to the new tab?
        $I->switchToNextTab();
        //$I->waitForElement("embed[type='application/pdf']");
        $I->wait(3);

        $I->takeFullPageScreenshot("account-center-view-proposal-pdf");
    }
}