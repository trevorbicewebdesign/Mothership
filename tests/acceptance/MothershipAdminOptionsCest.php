<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;


class MothershipAdminOptionsCest
{

    private $clientData;
    private $userData;
    private $accountData;

    const OPTIONS_URL = "/administrator/index.php?option=com_config&view=component&component=com_mothership";
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
     * @group options
     */
    public function MothershipViewOptions(AcceptanceTester $I)
    {
        $I->amOnPage(self::OPTIONS_URL);
        $I->waitForText("Mothership: Configuration", 10);

        $I->makeScreenshot("mothership-view-options");

        $I->seeElement("label[for='jform_company_name']");
        $I->seeElement("#jform_company_name");
        $I->seeElement("label[for='jform_company_email']");
        $I->seeElement("#jform_company_email");
        $I->seeElement("label[for='jform_company_address_1']");
        $I->seeElement("#jform_company_address_1");
        $I->seeElement("label[for='jform_company_address_2']");
        $I->seeElement("#jform_company_address_2");
        $I->seeElement("label[for='jform_company_city']");
        $I->seeElement("#jform_company_city");
        $I->seeElement("label[for='jform_company_state']");
        $I->seeElement("#jform_company_state");
        $I->seeElement("#jform_company_zip");
        $I->seeElement("label[for='jform_company_zip']");
        $I->seeElement("#jform_company_phone");
        $I->seeElement("label[for='jform_company_phone']");
        $I->seeElement("#jform_company_default_rate");
        $I->seeElement("label[for='jform_company_default_rate']");

        $I->see("Company Name *", "label[for='jform_company_name']");
        $I->see("Primary Email *", "label[for='jform_company_email']");
        $I->see("Address 1 *", "label[for='jform_company_address_1']");
        $I->see("Address 2", "label[for='jform_company_address_2']");
        $I->see("City *", "label[for='jform_company_city']");
        $I->see("State *", "label[for='jform_company_state']");
        $I->see("Zip *", "label[for='jform_company_zip']");
        $I->see("Primary Phone *", "label[for='jform_company_phone']");
        $I->see("Default Rate *", "label[for='jform_company_default_rate']");

        $I->click("Save", "#toolbar-apply");

        $I->waitForText("The form cannot be submitted as it's missing required data.", 10);
        $I->waitForText("Please correct the marked fields and try again.", 10);

        $I->seeElement("#jform_company_name.form-control-danger");
        $I->seeElement("#jform_company_email.form-control-danger");
        $I->seeElement("#jform_company_address_1.form-control-danger");
        $I->seeElement("#jform_company_city.form-control-danger");
        $I->seeElement("#jform_company_state.form-control-danger");
        $I->seeElement("#jform_company_zip.form-control-danger");
        $I->seeElement("#jform_company_phone.form-control-danger");
        $I->seeElement("#jform_company_default_rate.form-control-danger");

        $I->fillField("#jform_company_name", "Trevor Bice Webdesign");
        $I->fillField("#jform_company_email", "trevorbicewebdesign@gmail.com");
        $I->fillField("#jform_company_address_1", "370 Garden Lane");
        $I->fillField("#jform_company_address_2", "");
        $I->fillField("#jform_company_city", "Bayside");
        $I->selectOption("#jform_company_state", "CA");
        $I->fillField("#jform_company_zip", "95524");
        $I->fillField("#jform_company_phone", "(707) 880-0156");
        $I->fillField("#jform_company_default_rate", "70.00");

        $I->click("Save", "#toolbar-apply");

        $I->waitForText("Configuration saved.", 10);

    }
}