<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * Portal users on the front end: someone given access to a client sees it, and an invited
 * person sets their password from the link they were sent.
 */
class MothershipFrontPortalUsersCest
{
    private $ownerData;
    private $joomlaUserData;
    private $clientData;
    private $hiddenClientData;

    const CLIENTS_VIEW_ALL_URL = "index.php?option=com_mothership&view=clients";
    const INVITE_URL = "index.php?option=com_mothership&view=invite&token=%s";

    const PASSWORD = 'Str0ng&Lengthy-Passw0rd';

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->ownerData = $I->createJoomlaUser([]);
        $this->joomlaUserData = $I->createJoomlaUser([]);

        $this->clientData = $I->createMothershipClient([
            'name' => 'Granted Client',
            'owner_user_id' => $this->ownerData['id'],
        ]);

        $this->hiddenClientData = $I->createMothershipClient([
            'name' => 'Hidden Client',
            'owner_user_id' => $this->ownerData['id'],
        ]);
    }

    /**
     * @group frontend
     * @group portaluser
     * @group frontend-portaluser
     */
    public function AdministratorSeesTheClientTheyWereGiven(AcceptanceTester $I)
    {
        $I->createMothershipUser([
            'user_id' => $this->joomlaUserData['id'],
            'client_id' => $this->clientData['id'],
        ]);

        $I->logInAs($this->joomlaUserData['username'], '4&GoH#7FvPsY');
        $I->see("Hi {$this->joomlaUserData['name']},");

        $I->amOnPage(self::CLIENTS_VIEW_ALL_URL);
        $I->waitForText("Clients", 10, "h1");
        $I->dontSee("Warning:");

        // The client they were given, and not the owner's other one.
        $I->seeNumberOfElements("table#clientsTable tbody tr", 1);
        $I->see("Granted Client", "table#clientsTable tbody tr td:nth-child(2)");
        $I->dontSee("Hidden Client");
    }

    /**
     * @group frontend
     * @group portaluser
     * @group frontend-portaluser
     */
    public function InvitedPersonSetsTheirPasswordAndIsSignedIn(AcceptanceTester $I)
    {
        $token = bin2hex(random_bytes(32));

        $access = $I->createMothershipUser([
            'user_id' => $this->joomlaUserData['id'],
            'client_id' => $this->clientData['id'],
            'accepted' => null,
            'invite_token_hash' => hash('sha256', $token),
            'invite_expires' => '2099-01-01 00:00:00',
        ]);

        $I->amOnPage(sprintf(self::INVITE_URL, $token));
        $I->waitForText("Welcome, " . strtok($this->joomlaUserData['name'], ' '), 10, "h1");
        $I->dontSee("Warning:");
        $I->see("Administrator access to the Granted Client portal");
        $I->seeInField("#invite-email", $this->joomlaUserData['email']);

        $I->takeFullPageScreenshot("account-center-invite");

        // Two passwords that differ leave the invite waiting.
        $I->fillField("#invite-password", self::PASSWORD);
        $I->fillField("#invite-password2", self::PASSWORD . 'x');
        $I->click("Set password and sign in");
        $I->waitForText("The passwords don't match.", 10);
        $I->seeInDatabase("jos_mothership_users", ['id' => $access['id'], 'accepted' => null]);

        $I->fillField("#invite-password", self::PASSWORD);
        $I->fillField("#invite-password2", self::PASSWORD);
        $I->click("Set password and sign in");

        // Signed in, and looking at the client.
        $I->waitForText("Your password is set. Welcome to the Granted Client portal.", 10);
        $I->waitForText("Clients", 10, "h1");
        $I->see("Granted Client", "table#clientsTable tbody tr td:nth-child(2)");
        $I->dontSee("Hidden Client");

        $I->dontSeeInDatabase("jos_mothership_users", ['id' => $access['id'], 'accepted' => null]);
        $I->seeInDatabase("jos_mothership_users", ['id' => $access['id'], 'invite_token_hash' => null]);
        $I->seeInDatabase("jos_mothership_logs", ['object_type' => 'user_access', 'object_id' => $access['id'], 'action' => 'accepted', 'user_id' => $this->joomlaUserData['id']]);

        // The link works once.
        $I->amOnPage(sprintf(self::INVITE_URL, $token));
        $I->waitForText("This invite link has expired", 10, "h1");
    }

    /**
     * @group frontend
     * @group portaluser
     * @group frontend-portaluser
     */
    public function ExpiredOrUnknownInviteLink(AcceptanceTester $I)
    {
        $token = bin2hex(random_bytes(32));

        $I->createMothershipUser([
            'user_id' => $this->joomlaUserData['id'],
            'client_id' => $this->clientData['id'],
            'accepted' => null,
            'invite_token_hash' => hash('sha256', $token),
            'invite_expires' => '2020-01-01 00:00:00',
        ]);

        foreach ([$token, str_repeat('a', 64), 'nonsense'] as $link) {
            $I->amOnPage(sprintf(self::INVITE_URL, $link));
            $I->waitForText("This invite link has expired", 10, "h1");
            $I->dontSee("Warning:");
            $I->dontSeeElement("#invite-form");
        }
    }
}
