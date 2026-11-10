<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * The account center is scoped to the signed-in user's own clients. Unlike the other
 * front-end tests, the user here is an ordinary Registered user, not a Super User.
 */
class MothershipFrontAccessCest
{
    private $joomlaUserData;
    private $clientData;
    private $accountData;
    private $invoiceData;
    private $otherClientData;
    private $otherAccountData;
    private $otherInvoiceData;
    private $otherProposalData;

    const NOT_AUTHORISED = "You are not authorised to view this resource.";

    const INVOICE_VIEW_URL = "index.php?option=com_mothership&view=invoice&layout=default&id=%s";
    const INVOICE_PAY_URL = "index.php?option=com_mothership&task=invoice.payment&id=%s";
    const INVOICE_PDF_URL = "index.php?option=com_mothership&task=invoice.downloadPdf&id=%s";
    const PROPOSAL_VIEW_URL = "index.php?option=com_mothership&view=proposal&layout=default&id=%s";
    const PROPOSAL_APPROVE_URL = "index.php?option=com_mothership&task=proposal.approve&id=%s";
    const PROPOSAL_PDF_URL = "index.php?option=com_mothership&task=proposal.downloadPdf&id=%s";
    const ACCOUNT_VIEW_URL = "index.php?option=com_mothership&view=account&id=%s";
    const CLIENT_VIEW_URL = "index.php?option=com_mothership&view=client&id=%s";
    const ACCOUNTS_VIEW_ALL_URL = "index.php?option=com_mothership&view=accounts";

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $I->setMothershipConfig([
            'company_name' => 'A Fake Company',
            'company_email' => 'test.company@mailinator.com',
        ]);

        // createJoomlaUser() makes a Super User; demote this one to Registered.
        $this->joomlaUserData = $I->createJoomlaUser([]);
        $I->updateInDatabase("jos_user_usergroup_map", ['group_id' => 2], ['user_id' => $this->joomlaUserData['id']]);

        $this->clientData = $I->createMothershipClient([
            'name' => 'My Client',
            'owner_user_id' => $this->joomlaUserData['id'],
        ]);
        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'My Account',
        ]);
        $this->invoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'number' => '70017001',
            'status' => 2,
        ]);

        // A second client that the signed-in user has nothing to do with.
        $otherUserData = $I->createJoomlaUser(['username' => 'someoneelse']);
        $this->otherClientData = $I->createMothershipClient([
            'name' => 'Somebody Elses Client',
            'owner_user_id' => $otherUserData['id'],
        ]);
        $this->otherAccountData = $I->createMothershipAccount([
            'client_id' => $this->otherClientData['id'],
            'name' => 'Somebody Elses Account',
        ]);
        $this->otherInvoiceData = $I->createMothershipInvoice([
            'client_id' => $this->otherClientData['id'],
            'account_id' => $this->otherAccountData['id'],
            'number' => '80028002',
            'status' => 2,
        ]);
        $this->otherProposalData = $I->createMothershipProposal([
            'client_id' => $this->otherClientData['id'],
            'account_id' => $this->otherAccountData['id'],
            'number' => '90039003',
            'status' => 2,
        ]);
    }

    private function logIn(AcceptanceTester $I)
    {
        $I->amOnPage("/");
        $I->fillField(".mod-login input[name=username]", strtolower($this->joomlaUserData['username']));
        $I->fillField(".mod-login input[name=password]", '4&GoH#7FvPsY');
        $I->click(".mod-login button[type=submit]");
        $I->wait(1);
        $I->see("Hi {$this->joomlaUserData['name']},");
    }

    /**
     * Posts to a task with the session's CSRF token, the way a hand-built request would.
     */
    private function postTask(AcceptanceTester $I, string $task, int $id)
    {
        $I->executeJS("
            var token = Array.from(document.querySelectorAll('input[type=hidden]'))
                .find(function (input) { return /^[0-9a-f]{32}$/.test(input.name); });
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'index.php?option=com_mothership&task=' + arguments[0];
            form.innerHTML = '<input name=\"id\" value=\"' + arguments[1] + '\">'
                + '<input name=\"payment_method\" value=\"zelle\">'
                + '<input name=\"' + token.name + '\" value=\"1\">';
            document.body.appendChild(form);
            form.submit();
        ", [$task, $id]);
        $I->wait(2);
    }

    /**
     * @group frontend
     * @group access
     */
    public function RegisteredOwnerSeesOwnRecords(AcceptanceTester $I)
    {
        $this->logIn($I);

        $I->amOnPage(sprintf(self::INVOICE_VIEW_URL, $this->invoiceData['id']));
        $I->dontSee(self::NOT_AUTHORISED);
        $I->see($this->invoiceData['number']);

        $I->amOnPage(self::ACCOUNTS_VIEW_ALL_URL);
        $I->dontSee(self::NOT_AUTHORISED);
        $I->see($this->accountData['name']);
        $I->dontSee($this->otherAccountData['name']);

        $I->amOnPage(sprintf(self::ACCOUNT_VIEW_URL, $this->accountData['id']));
        $I->dontSee(self::NOT_AUTHORISED);
        $I->see($this->accountData['name']);
    }

    /**
     * @group frontend
     * @group access
     */
    public function CannotSeeAnotherClientsRecords(AcceptanceTester $I)
    {
        $this->logIn($I);

        $I->amOnPage(sprintf(self::INVOICE_VIEW_URL, $this->otherInvoiceData['id']));
        $I->see("Invoice not found");
        $I->dontSee($this->otherInvoiceData['number']);

        $I->amOnPage(sprintf(self::INVOICE_PAY_URL, $this->otherInvoiceData['id']));
        $I->dontSee($this->otherInvoiceData['number']);

        $I->amOnPage(sprintf(self::INVOICE_PDF_URL, $this->otherInvoiceData['id']));
        $I->dontSee($this->otherInvoiceData['number']);

        $I->amOnPage(sprintf(self::PROPOSAL_VIEW_URL, $this->otherProposalData['id']));
        $I->see("Proposal not found");
        $I->dontSee($this->otherProposalData['number']);

        $I->amOnPage(sprintf(self::PROPOSAL_APPROVE_URL, $this->otherProposalData['id']));
        $I->dontSee("Approve Proposal", "h1");

        $I->amOnPage(sprintf(self::PROPOSAL_PDF_URL, $this->otherProposalData['id']));
        $I->dontSee($this->otherProposalData['number']);

        $I->amOnPage(sprintf(self::ACCOUNT_VIEW_URL, $this->otherAccountData['id']));
        $I->see("Account not found");
        $I->dontSee($this->otherAccountData['name']);

        $I->amOnPage(sprintf(self::CLIENT_VIEW_URL, $this->otherClientData['id']));
        $I->see("Client not found");
        $I->dontSee($this->otherClientData['name']);
    }

    /**
     * @group frontend
     * @group access
     */
    public function CannotActOnAnotherClientsRecords(AcceptanceTester $I)
    {
        $this->logIn($I);

        $this->postTask($I, 'proposal.approveConfirm', $this->otherProposalData['id']);
        $I->seeInDatabase("jos_mothership_proposals", ['id' => $this->otherProposalData['id'], 'status' => 2]);

        $I->amOnPage("/");
        $this->postTask($I, 'proposal.denyConfirm', $this->otherProposalData['id']);
        $I->seeInDatabase("jos_mothership_proposals", ['id' => $this->otherProposalData['id'], 'status' => 2]);

        $I->amOnPage("/");
        $this->postTask($I, 'invoice.processPayment', $this->otherInvoiceData['id']);
        $I->dontSeeInDatabase("jos_mothership_payments", ['client_id' => $this->otherClientData['id']]);
    }

    /**
     * @group frontend
     * @group access
     */
    public function GuestCannotSeeOrActOnRecords(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::INVOICE_VIEW_URL, $this->invoiceData['id']));
        $I->see("Invoice not found");
        $I->dontSee($this->invoiceData['number']);

        $I->amOnPage(self::ACCOUNTS_VIEW_ALL_URL);
        $I->see(self::NOT_AUTHORISED);

        $I->amOnPage(sprintf(self::PROPOSAL_PDF_URL, $this->otherProposalData['id']));
        $I->dontSee($this->otherProposalData['number']);

        $I->amOnPage("/");
        $this->postTask($I, 'proposal.approveConfirm', $this->otherProposalData['id']);
        $I->seeInDatabase("jos_mothership_proposals", ['id' => $this->otherProposalData['id'], 'status' => 2]);

        $I->amOnPage("/");
        $this->postTask($I, 'invoice.processPayment', $this->invoiceData['id']);
        $I->dontSeeInDatabase("jos_mothership_payments", ['client_id' => $this->clientData['id']]);
    }
}
