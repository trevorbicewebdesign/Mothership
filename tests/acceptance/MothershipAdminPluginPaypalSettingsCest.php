<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;


class MothershipAdminPluginPaypalSettingsCest
{

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        // Navigate to the login page
        $I->amOnPage("/administrator/");

        // Log in with valid credentials
        $I->fillField("input[name=username]", "admin");
        $I->fillField("input[name=passwd]", "password123!test");
        $I->click("Log in");
        $I->wait(3);
    }

    /**
     * @group backend
     * @group payment
     */
    public function MothershipViewPluginPaypalSettings(AcceptanceTester $I)
    {
        $I->amOnPage("/administrator/index.php?option=com_plugins&view=plugins");
        $I->waitForText("Plugins", 20, "h1.page-title");

        $I->fillField("#filter_search", "Mothership");
        $I->pressKey("#filter_search", WebDriverKeys::ENTER);
        $I->wait(3);

        $I->see("Mothership Payment - PayPal");
        $I->click("Mothership Payment - PayPal");
        $I->waitForText("Plugins: Mothership Payment - PayPal", 20, "h1.page-title");
        $I->makeScreenshot("mothership-payments-paypal-settings");

        $I->see("Payment Display Label", "label");
        $I->see("Client ID *", "label");
        $I->see("Client Secret *", "label");
        $I->see("Sandbox Mode", "label");
        $I->see("Currency", "label");
        $I->see("Business Email *", "label");
    }
}