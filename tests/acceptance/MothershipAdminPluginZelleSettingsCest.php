<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;


class MothershipAdminPluginZelleSettingsCest
{

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        // Navigate to the login page
        $I->amOnPage("/administrator/");

        // Log in with valid credentials
        $I->fillField("input[name=username]", "adminuser");
        $I->fillField("input[name=passwd]", "password123!test");
        $I->click("Log in");
        $I->wait(3);
    }

    /**
     * @group backend
     * @group payment
     */
    public function MothershipViewPluginZelleSettings(AcceptanceTester $I)
    {
        $I->amOnPage("/administrator/index.php?option=com_plugins&view=plugins");
        $I->waitForText("Plugins", 20, "h1.page-title");

        $I->fillField("#filter_search", "Mothership");
        $I->pressKey("#filter_search", WebDriverKeys::ENTER);
        $I->wait(3);

        $I->see("Mothership Payment - Zelle");
        $I->click("Mothership Payment - Zelle");
        $I->waitForText("Plugins: Mothership Payment - Zelle", 20, "h1.page-title");
        $I->makeScreenshot("mothership-payments-zelle-settings");

        $I->see("Payment Display Label", "label");
        $I->see("Zelle Email or Phone Number", "label");
        $I->see("Instructions", "label");
    }
}