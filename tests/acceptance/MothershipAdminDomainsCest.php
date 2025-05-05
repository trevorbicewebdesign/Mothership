<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use DateTime;
use DateTimeZone;


class MothershipAdminDomainsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $invoiceData;
    private $paymentData;
    private $joomlaUserData;

    const DOMAINS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=domains";
    const DOMAIN_EDIT_URL = "/administrator/index.php?option=com_mothership&view=domain&layout=edit&id=%s";

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $this->joomlaUserData = $I->createJoomlaUser([
            'name' => 'Test Smith',
        ], 10);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->invoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '100.00',
            'number' => 1000,
            'created' => date('Y-m-d'),
            'status' => 1,
        ]);

        $this->paymentData = $I->createMothershipPaymentData([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
        ]);

        $I->amOnPage("/administrator/");
        $I->fillField("input[name=username]", "admin");
        $I->fillField("input[name=passwd]", "password123!test");
        $I->click("Log in");
        $I->waitForText("Hide Forever");
        $I->click("Hide Forever");
    }

    /**
     * @group backend
     * @group domain
     * @group backend-domain
     */
    public function MothershipViewDomains(AcceptanceTester $I)
    {
        $domainData = $I->createMothershipDomain([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'example.com',
            'registrar' => 'GoDaddy',
            'reseller' => 'GoDaddy',
            'dns_provider' => 'cloudflare',
            'created' => "2020-01-01 00:00:00",
        ]);

        $I->amOnPage(self::DOMAINS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Domains", 20, "h1.page-title");
        
        $I->makeScreenshot("mothership-domains-view-all");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $created = date("Y-m-d", strtotime($domainData['created']));

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");
        $I->see("Id", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Name", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("Client", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Account", "#j-main-container table thead tr th:nth-child(5)");
        $I->see("Registrar", "#j-main-container table thead tr th:nth-child(6)");
        $I->see("Reseller", "#j-main-container table thead tr th:nth-child(7)");
        $I->see("DNS Provider", "#j-main-container table thead tr th:nth-child(8)");
        $I->see("Created", "#j-main-container table thead tr th:nth-child(9)");

        $I->see("{$domainData['id']}", "#j-main-container table tbody tr td:nth-child(2)");
        $I->see("{$domainData['name']}", "#j-main-container table tbody tr td:nth-child(3)");
        $I->see("{$this->clientData['name']}", "#j-main-container table tbody tr td:nth-child(4)");
        $I->see("{$this->accountData['name']}", "#j-main-container table tbody tr td:nth-child(5)");
        $I->see("{$domainData['registrar']}", "#j-main-container table tbody tr td:nth-child(6)");
        $I->see("{$domainData['reseller']}", "#j-main-container table tbody tr td:nth-child(7)");
        $I->see("{$domainData['dns_provider']}", "#j-main-container table tbody tr td:nth-child(8)");
        $I->see("{$created}", "#j-main-container table tbody tr td:nth-child(9)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 1);
    }

    /**
     * @group backend
     * @group domain
     * @group backend-domain
     */
    public function MothershipAddDomain(AcceptanceTester $I)
    {
        $I->amOnPage(self::DOMAINS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Domains", 20, "h1.page-title");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $toolbarStatusGroup = "#toolbar-status-group";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->click("{$toolbar} {$toolbarNew} .btn.button-new");
        $I->wait(1);
        $I->see("Mothership: New Domain", "h1.page-title");

        $I->makeScreenshot("mothership-domain-add-details");

        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        $I->seeElement("form[name=adminForm]");
        $I->seeElement("form#domain-form");

        $I->seeElement("#myTab");
        $I->see("Domain Details", "#myTab button[aria-controls=details]");

        $I->seeElement("select#jform_client_id");
        $I->seeElement("input#jform_name");

        $I->selectOption("select#jform_client_id", "Test Client");
        $I->selectOption("select#jform_account_id", "Test Account");
        $I->fillField("input#jform_name", "example.com");
        $I->fillField("input#jform_registrar", "GoDaddy");
        $I->fillField("input#jform_reseller", "GoDaddy");
        $I->selectOption("select#jform_dns_provider", "cloudflare");
        $I->fillField("input#jform_purchase_date", "2020-01-01 00:00:00");

        $I->click("Save", "#toolbar");
        $I->wait(1);
        $I->waitForText("Mothership: Edit Domain", 10, "h1.page-title");

        $I->waitForText("Domain example.com saved successfully.", 10, ".alert-message");

        $I->seeInField("input#jform_name", "example.com");
        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");

        $I->seeInDatabase("jos_mothership_domains", [
            'name' => 'example.com',
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'registrar' => 'godaddy',
            'reseller' => 'godaddy',
            'dns_provider' => 'cloudflare',
            'purchase_date' => '2020-01-01 00:00:00',
        ]);

    }

    /**
     * @group backend
     * @group domain
     * @group delete
     * @group backend-domain
     */
    public function MothershipDeleteDomain(AcceptanceTester $I)
    {
        $clientData = $I->createMothershipClient([
            'name' => 'Test Client 2',
        ]);

        $accountData = $I->createMothershipAccount([
            'client_id' => $clientData['id'],
            'name' => 'Test Account 2',
        ]);

        $domainData = $I->createMothershipDomain([
            'client_id' => $clientData['id'],
            'account_id' => $accountData['id'],
            'name' => 'example2.com',
        ]);

        $I->seeInDatabase("jos_mothership_domains", [ 'id' => $domainData['id'] ]);
        $I->amOnPage(self::DOMAINS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Domains", 20, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);

        $I->seeElement(".btn-toolbar");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "domains.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "domain.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "domains.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::DOMAINS_VIEW_ALL_URL);
        $I->see("Mothership: Domains", "h1.page-title");
        $I->see("1 Domain deleted successfully.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table tbody tr", 0);

        $I->dontSeeInDatabase("jos_mothership_domains", [ 'id' => $domainData['id'] ]);
    }

    /**
     * @group backend
     * @group domain
     * @group delete
     * @group backend-domain
     */
    public function MothershipScanDomain(AcceptanceTester $I)
    {
        $clientData = $I->createMothershipClient([
            'name' => 'Test Client 2',
        ]);

        $accountData = $I->createMothershipAccount([
            'client_id' => $clientData['id'],
            'name' => 'Test Account 2',
        ]);

        $domainData = $I->createMothershipDomain([
            'client_id' => $clientData['id'],
            'account_id' => $accountData['id'],
            'name' => 'google.com',
            'epp_status' => json_encode([]  ),
        ]);

        $I->seeInDatabase("jos_mothership_domains", [ 'id' => $domainData['id'] ]);
        $I->amOnPage( sprintf(self::DOMAIN_EDIT_URL, $domainData['id']) );
        $I->waitForText("Mothership: Edit Domain", 10, "h1.page-title");

        $I->see("WHOIS Scan & Update", "joomla-toolbar-button#toolbar-refresh");
        $I->seeElement("joomla-toolbar-button#toolbar-refresh", ['task' => "domain.whoisScan"]);

        $I->click("WHOIS Scan & Update", "#toolbar");
        $I->waitForText("Domain {$domainData['name']} WHOIS scan completed successfully.", 10, ".alert-message");
        $I->seeInCurrentUrl( sprintf(self::DOMAIN_EDIT_URL, $domainData['id']));

        $domain = $I->grabDomainFromDatabase($domainData['id']);
        codecept_debug($domain);

        $epp_status = $domain['epp_status'];
        codecept_debug($epp_status);

        $I->assertEquals([
            "clientUpdateProhibited",
            "clientTransferProhibited",
            "clientDeleteProhibited",
            "serverUpdateProhibited",
            "serverTransferProhibited",
            "serverDeleteProhibited"
        ], $epp_status);

        $I->seeInDatabase("jos_mothership_domains", [
            'id' => $domainData['id'],
            'name' => 'google.com',
            'client_id' => $clientData['id'],
            'account_id' => $accountData['id'],
            'registrar' => 'MarkMonitor, Inc.',
            'reseller' => '',
            'purchase_date' => '1997-09-15 07:00:00',
            'expiration_date' => '2028-09-13 07:00:00',
        ]);

        $I->seeInDatabase("jos_mothership_logs", [
            'client_id' => $clientData['id'],
            'account_id' => $accountData['id'],
            'object_type' => 'domain',
            'object_id' => $domainData['id'],
            'action' => 'scanned',
        ]);
    }
}