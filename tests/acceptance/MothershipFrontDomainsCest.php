<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipFrontDomainsCest
{
    private $clientData;
    private $userData;
    private $accountData;
    private $domainData;
    private $invoiceData;
    private $mothershipConfig;
    private $joomlaUserData;
    private $invoiceItemData = [];

    const DOMAINS_VIEW_ALL_URL = "index.php?option=com_mothership&view=domains";
    const DOMAINS_VIEW_ALL_SEF_URL = "/account-center/billing/domains/";

    const DOMAIN_VIEW_URL = "index.php?option=com_mothership&view=domain&layout=default&id=%s";
    const DOMAIN_VIEW_SEF_URL = "/account-center/billing/domains/%s/";

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

        $this->invoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '100.00',
            'number' => '1000',
            'status' => 2,
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

        $I->amOnPage("/");
        $I->fillField(".mod-login input[name=username]", strtolower($this->joomlaUserData['username']));
        $I->fillField(".mod-login input[name=password]", '4&GoH#7FvPsY');
        $I->click(".mod-login button[type=submit]");
        $I->wait(1);
        $I->see("Hi {$this->joomlaUserData['name']},");
    }

    /**
     * @group frontend
     * @group domain
     * @group frontend-domain
     */
    public function ViewAllDomainsPage(AcceptanceTester $I)
    {
        $domainData = $I->createMothershipDomain([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'example.com',
            'registrar' => 'godaddy',
            'reseller' => 'godaddy',
            'dns_provider' => 'coudflare',
            'created' => "2020-01-01 00:00:00",
            'status' => 'active',
        ]);
        // Verify redirection to account center
        $I->amOnPage(self::DOMAINS_VIEW_ALL_URL);
        $I->waitForText("Domains", 10, "h1");

        $I->takeFullPageScreenshot("account-center-view-all-domains");
        $I->dontSee("Warning:");

        // Confirm the correct number of records
        $I->seeNumberOfElements("table#domainsTable tbody tr", 1);

        // Confirm the table headers
        $I->see("#", "table#domainsTable thead tr th:nth-child(1)");
        $I->see("Domain", "table#domainsTable thead tr th:nth-child(2)");
        $I->see("Client", "table#domainsTable thead tr th:nth-child(3)");
        $I->see("Account", "table#domainsTable thead tr th:nth-child(4)");
        $I->see("Registrar", "table#domainsTable thead tr th:nth-child(5)");
        $I->see("Reseller", "table#domainsTable thead tr th:nth-child(6)");
        $I->see("DNS", "table#domainsTable thead tr th:nth-child(7)");
        $I->see("Created", "table#domainsTable thead tr th:nth-child(8)");
        $I->see("Status", "table#domainsTable thead tr th:nth-child(9)");

        // Confirm the table data
        $I->see("{$domainData['id']}", "table#domainsTable tbody tr td:nth-child(1)");
        $I->see($domainData['name'], "table#domainsTable tbody tr td:nth-child(2)");
        $I->see($this->clientData['name'], "table#domainsTable tbody tr td:nth-child(3)");
        $I->see($this->accountData['name'], "table#domainsTable tbody tr td:nth-child(4)");
        $I->see($domainData['registrar'], "table#domainsTable tbody tr td:nth-child(5)");
        $I->see($domainData['reseller'], "table#domainsTable tbody tr td:nth-child(6)");
        $I->see($domainData['dns_provider'], "table#domainsTable tbody tr td:nth-child(7)");
        $I->see(date('Y-m-d', strtotime($domainData['created'])), "table#domainsTable tbody tr td:nth-child(8)");
        $I->see($domainData['status'], "table#domainsTable tbody tr td:nth-child(9)");

    }

    /**
     * @group frontend
     * @group domain
     * @group frontend-domain
     */
    public function ViewDomainPage(AcceptanceTester $I)
    {
        $domainData = $I->createMothershipDomain([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'example.com',
        ]);
        $I->amOnPage(sprintf(self::DOMAIN_VIEW_URL, $domainData['id']));
        $log_created = date('Y-m-d H:i:s');
        $I->waitForText("Domain: {$domainData['name']}", 10, "h1");
    
        $I->takeFullPageScreenshot("account-center-view-domain");
        $I->dontSee("Warning:");

        $created = $I->grabFromDatabase("jos_mothership_logs", "created", [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'user_id' => $this->joomlaUserData['id'],            
            'action' => 'viewed',
            'object_type' => 'domain',
            'object_id' => $domainData['id'],
        ]);

        $timeDifference = abs(strtotime($log_created) - strtotime($created));
        $I->assertLessThanOrEqual(2, $timeDifference, "Log created date should not differ by more than 2 seconds.");
    }

}