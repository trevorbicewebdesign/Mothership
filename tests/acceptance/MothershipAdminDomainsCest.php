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
    private $form_fields;

    public function _before(AcceptanceTester $I)
    {
        $this->form_fields = [
            'client_id'=>['type'=>'select','required'=>true],
            'account_id'=>['type'=>'select','required'=>true],
            'name'=>['type'=>'text','required'=>true],
        ];

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
        $I->waitForJoomlaHeading("Domains", $I);        
        $I->makeScreenshot("mothership-domains-view-all");
        $I->dontSee("Warning:");
        $I->validateJoomlaItemActions(['New', ], $I);
        $created = date("Y-m-d", strtotime($domainData['created']));
        $I->validateJoomlaViewAllTableHeaders([
            "Id"=>2,
            "Name"=>3,
            "Client"=>4,
            "Account"=>5,
            "Registrar"=>6,
            "Reseller"=>7,
            "DNS Provider"=>8,
            "Created"=>9,
        ], $I);
        $I->validateJoomlaViewAllTableRowData(1, [
            'Id' => ['value' => $domainData['id'], 'position' => 2],
            'Name' => ['value' => $domainData['name'], 'position' => 3],
            'Client' => ['value' => $this->clientData['name'], 'position' => 4],
            'Account' => ['value' => $this->accountData['name'], 'position' => 5],
            'Registrar' => ['value' => $domainData['registrar'], 'position' => 6],
            'Reseller' => ['value' => $domainData['reseller'], 'position' => 7],
            'DNS Provider' => ['value' => $domainData['dns_provider'], 'position' => 8],
            'Created' => ['value' => $created, 'position' => 9],
         ], $I);
        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 1);
        $I->see("1 - 1 / 1 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group domain
     * @group backend-domain
     */
    public function MothershipAddDomain(AcceptanceTester $I)
    {
        $I->amOnPage(self::DOMAINS_VIEW_ALL_URL);
        $I->waitForJoomlaHeading("Domains", $I);
        $I->validateJoomlaItemActions(['New', ], $I);
        $I->click("New");
        // Wait for the new domain form to load
        $I->waitForJoomlaHeading("New Domain", $I);
        $I->makeScreenshot("mothership-domain-add-details");
        $I->dontSee("Warning");
        $I->validateJoomlaItemActions([ 'Save', 'Save & Close', 'Cancel' ], $I);
        $I->validateJoomlaForm("domain-form", $this->form_fields, $I);
        // Check that the tab exists
        $I->seeElement("#myTab");
        $I->see("Domain Details", "#myTab button[aria-controls=details]");
        // SAVE WITHOUT ENTERING DATA
        $I->click("Save", "#toolbar");
        $I->waitForJoomlaHeading("New Domain", $I);
        $I->makeScreenshot("mothership-domain-add-errors");

        // VERIFY & FILL FORM FIELDS
        // Initially only the client is visible until a value is selected
        $I->seeElement("select#jform_client_id");
        $I->dontSee("select#jform_account_id");
        $I->seeElement("input#jform_name");
        // Select a Client to load Accounts
        $I->selectOption("select#jform_client_id", $this->clientData['id']);
        $I->wait(1);
        // Confirm Client is selected
        $I->seeOptionIsSelected("select#jform_client_id", "{$this->clientData['name']}");
        // Select an Account
        $I->selectOption("select#jform_account_id", $this->accountData['id']);
        $I->wait(1);
        // Confirm Account is selected
        $I->seeOptionIsSelected("select#jform_account_id", "{$this->accountData['name']}");
        
        $I->fillField("input#jform_name", "not a valid domain");
        $I->fillField("input#jform_registrar", "GoDaddy");
        $I->fillField("input#jform_reseller", "GoDaddy");
        $I->selectOption("select#jform_dns_provider", "cloudflare");
        $I->fillField("input#jform_purchase_date", "2020-01-01 00:00:00");

        $I->click("Save", "#toolbar");
        $I->waitForJoomlaHeading("New Domain", $I);
        $I->see("The form cannot be submitted as it's missing required data.", "#system-message-container .alert-message");

        $I->fillField("input#jform_name", "example.com");
        // VERIFY SAVE ACTION SUCCESS
        $I->click("Save", "#toolbar");
        $I->waitForJoomlaHeading("Edit Domain", $I);
        $I->waitForText("Domain example.com saved successfully.", 20, ".alert-message");

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

        $domain_id = $I->grabFromDatabase("jos_mothership_domains", 'id',[
            'name' => 'example.com',
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'registrar' => 'godaddy',
            'reseller' => 'godaddy',
            'dns_provider' => 'cloudflare',
            'purchase_date' => '2020-01-01 00:00:00',
        ]);

        // VERIFY CLOSE ACTION SUCCESS
        $I->click("Close", "#toolbar");
        $I->waitForJoomlaHeading("Domains", $I);
        $I->seeInCurrentUrl(self::DOMAINS_VIEW_ALL_URL);
        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);

        // VERIFY SAVE AND CLOSE ACTION SUCCESS
        $I->amOnPage( sprintf(self::DOMAIN_EDIT_URL, $domain_id) );
        $I->waitForJoomlaHeading("Edit Domain", $I);
        $I->seeInField("input#jform_name", "example.com");
        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");
        $I->seeOptionIsSelected("select#jform_account_id", "Test Account");

        $I->click("Save & Close", "#toolbar");
        $I->waitForJoomlaHeading("Domains", $I);
        $I->seeInCurrentUrl(self::DOMAINS_VIEW_ALL_URL);
        $I->seeNumberOfElements("#j-main-container table tbody tr", 1);
        $I->dontSeeElement("span.icon-checkedout");
    }

    /**
     * @group backend
     * @group domain
     * @group backend-domain
     */
    public function MothershipEditInvalidDomain(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::DOMAIN_EDIT_URL, "9999"));
        $I->waitForJoomlaHeading("Domains", $I);
        $I->seeInCurrentUrl(self::DOMAINS_VIEW_ALL_URL);
        $I->waitForText("Domain not found. Please select a valid domain.", 30, "#system-message-container .alert-message");
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
        $I->waitForJoomlaHeading("Domains", $I);

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
        $I->waitForJoomlaHeading("Domains", $I);
        $I->seeInCurrentUrl(self::DOMAINS_VIEW_ALL_URL);
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
        $I->waitForJoomlaHeading("Edit Domain", $I);

        $I->see("WHOIS Scan & Update", "joomla-toolbar-button#toolbar-refresh");
        $I->seeElement("joomla-toolbar-button#toolbar-refresh", ['task' => "domain.whoisScan"]);

        $I->click("WHOIS Scan & Update", "#toolbar");
        $I->waitForText("Domain {$domainData['name']} WHOIS scan completed successfully.", 30, ".alert-message");
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