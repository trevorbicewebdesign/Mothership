<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;


class MothershipAdminPluginPayByCheckSettingsCest
{

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        // Navigate to the login page
        $I->amOnPage("/administrator/");

        // Log in with valid credentials
        $I->fillField("input[name=username]", "trevorbice");
        $I->fillField("input[name=passwd]", "4&GoH#7FvPsY");
        $I->click("Log in");
        $I->wait(3);
    }

    /**
     * @group backend
     * @group payment
     */
    public function MothershipViewPluginPayByCheckSettings(AcceptanceTester $I)
    {
        $I->amOnPage("/administrator/index.php?option=com_plugins&view=plugins");
        $I->waitForText("Plugins", 20, "h1.page-title");

        $I->fillField("#filter_search", "Mothership");
        $I->pressKey("#filter_search", WebDriverKeys::ENTER);
        $I->wait(3);

        $I->see("Mothership Payment - Pay By Check");
        $I->click("Mothership Payment - Pay By Check");
        $I->waitForText("Plugins: Mothership Payment - Pay By Check", 20, "h1.page-title");
        $I->makeScreenshot("mothership-payments-paybycheck-settings");

        $I->see("Payment Display Label", "label");
        $I->see("Check Payee", "label");
        $I->see("Instructions", "label");
    }
}