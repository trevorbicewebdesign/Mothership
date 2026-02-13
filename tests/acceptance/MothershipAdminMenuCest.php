<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class MothershipAdminMenuCest
{


    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage("/administrator/");
        $I->fillField("input[name=username]", "admin");
        $I->fillField("input[name=passwd]", "password123!test");
        $I->click("Log in");
        $I->waitForText("Hide Forever", 30);
        $I->click("Hide Forever");
    }

    /**
     * @group backend
     * @group menus
     * @group backend-menus
     */
    public function MothershipMenuItems(AcceptanceTester $I)
    {
        $I->amOnPage("/administrator/index.php?option=com_menus&view=items&menutype=mainmenu");
        $I->wait(1);
        $I->waitForText("Menus: Items (Main Menu)", 30);
        $I->click("New", "#toolbar-new");
        $I->wait(1);
        $I->waitForText("Menus: New Item", 30);
        $I->click("button[data-button-action=select]");
        $I->wait(1);
        $I->switchToIFrame(".iframe-content");
        $I->click("Mothership");
        $I->wait(1);
        $I->see("Accounts", ".accordion-body a.list-group-item");
        $I->see("Clients", ".accordion-body a.list-group-item");
        $I->see("Domains", ".accordion-body a.list-group-item");
        $I->see("Invoices", ".accordion-body a.list-group-item");
        $I->see("Payments", ".accordion-body a.list-group-item");
        $I->see("Projects", ".accordion-body a.list-group-item");
        $I->see("Proposals", ".accordion-body a.list-group-item");
        $I->switchToIFrame();
    }

    public function MothershipMenuAddAccountsPage(AcceptanceTester $I)
    {
        $I->amOnPage("/administrator/index.php?option=com_menus&view=items&menutype=mainmenu");
        $I->wait(1);
        $I->waitForText("Menus: Items (Main Menu)", 30);
        $I->click("New", "#toolbar-new");
        $I->wait(1);
        $I->waitForText("Menus: New Item", 30);
        $I->click("button[data-button-action=select]");
        $I->wait(1);
        $I->switchToIFrame(".iframe-content");
        $I->click("Mothership");
        $I->wait(1);
        $I->click("Accounts");
        $I->switchToIFrame();
        $I->wait(3);
        
        $I->seeInField("input#jform_type", "Accounts");
        $I->seeInField("input#jform_link", "index.php?option=com_mothership&view=accounts");

        $I->fillField("input#jform_title", "Accounts");
        $I->click("Save & Close", "#save-group-children-save");
        
        $I->wait(3);
        
        $I->see("Accounts");    
    }

}