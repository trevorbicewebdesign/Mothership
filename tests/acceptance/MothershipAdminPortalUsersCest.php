<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * Portal users in the administrator: the Portal Users list, one person's page, and the Users
 * tab on a client.
 */
class MothershipAdminPortalUsersCest
{
    private $ownerData;
    private $grantedData;
    private $pendingData;
    private $clientData;
    private $otherClientData;
    private $grantedAccess;
    private $pendingAccess;

    /** Unique to this run, so one test's addresses never match another's. */
    private $token;

    const PORTALUSERS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=portalusers";
    const PORTALUSER_VIEW_URL = "/administrator/index.php?option=com_mothership&view=portaluser&id=%s";
    const CLIENT_EDIT_URL = "/administrator/index.php?option=com_mothership&view=client&layout=edit&id=%s";

    const LIST_ROWS = "#portalUserList tbody tr";
    const PENDING_ROWS = "#pendingInvites tbody tr";
    const USERS_TAB = "button[aria-controls=users]";
    const CLIENT_USER_ROWS = "#clientUserList tbody tr";

    public function _before(AcceptanceTester $I)
    {
        $this->token = 'msusers' . bin2hex(random_bytes(4));

        $I->resetMothershipTables();

        $this->ownerData = $I->createJoomlaUser([
            'name' => 'Olive Owner',
            'username' => "olive.{$this->token}",
            'email' => "olive.{$this->token}@mailinator.com",
        ]);

        $this->grantedData = $I->createJoomlaUser([
            'name' => 'Gary Granted',
            'username' => "gary.{$this->token}",
            'email' => "gary.{$this->token}@mailinator.com",
        ]);

        $this->pendingData = $I->createJoomlaUser([
            'name' => 'Penny Pending',
            'username' => "penny.{$this->token}@mailinator.com",
            'email' => "penny.{$this->token}@mailinator.com",
        ]);

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
            'owner_user_id' => $this->ownerData['id'],
        ]);

        $this->otherClientData = $I->createMothershipClient([
            'name' => 'Other Client',
            'owner_user_id' => $this->ownerData['id'],
        ]);

        $this->grantedAccess = $I->createMothershipUser([
            'user_id' => $this->grantedData['id'],
            'client_id' => $this->clientData['id'],
        ]);

        $this->pendingAccess = $I->createMothershipUser([
            'user_id' => $this->pendingData['id'],
            'client_id' => $this->clientData['id'],
            'accepted' => null,
            'invite_token_hash' => hash('sha256', bin2hex(random_bytes(32))),
            'invite_expires' => '2099-01-01 00:00:00',
        ]);

        $I->loginAsAdmin();
    }

    /**
     * Say yes to a "are you sure". The pages ask in a native confirm; Joomla's toolbar asks in
     * one on older 5.x and in its own dialog on newer releases.
     */
    private function confirm(AcceptanceTester $I): void
    {
        $I->wait(1);

        try {
            $I->acceptPopup();
        } catch (\Throwable $e) {
            $I->click("Yes", "joomla-dialog");
        }

        $I->wait(1);
    }

    /**
     * @group backend
     * @group portaluser
     * @group backend-portaluser
     */
    public function MothershipViewPortalUsers(AcceptanceTester $I)
    {
        $I->amOnPage(self::PORTALUSERS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Portal Users", 30, "h1.page-title");
        $I->dontSee("Warning:");
        $I->seeElementInDOM("#sidebarmenu a[href*='view=portalusers']");

        $I->takeFullPageScreenshot("admin-portal-users-view-all");

        // One row per person, ordered by name.
        $I->seeNumberOfElements(self::LIST_ROWS, 3);
        $I->see("Gary Granted", self::LIST_ROWS . ":nth-child(1)");
        $I->see($this->grantedData['email'], self::LIST_ROWS . ":nth-child(1)");
        $I->see("Test Client", self::LIST_ROWS . ":nth-child(1)");
        $I->see("Administrator", self::LIST_ROWS . ":nth-child(1) .ms-roles");

        // Someone who owns two clients has them counted, not listed.
        $I->see("Olive Owner", self::LIST_ROWS . ":nth-child(2)");
        $I->see("2 clients", self::LIST_ROWS . ":nth-child(2)");
        $I->see("Owner", self::LIST_ROWS . ":nth-child(2) .ms-roles");

        $I->see("Penny Pending", self::LIST_ROWS . ":nth-child(3)");
        $I->see("1 pending", self::LIST_ROWS . ":nth-child(3)");

        // Invites nobody has accepted are also listed on their own.
        $I->see("Pending invites (1)", "#pendingInvites h2");
        $I->seeNumberOfElements(self::PENDING_ROWS, 1);
        $I->see("Penny Pending", self::PENDING_ROWS);
        $I->see("Test Client", self::PENDING_ROWS);

        // Search narrows the people.
        $I->fillField("#filter_search", "Gary");
        $I->click(".filter-search-bar__button");
        $I->waitForText("Gary Granted", 30, self::LIST_ROWS);
        $I->seeNumberOfElements(self::LIST_ROWS, 1);
    }

    /**
     * @group backend
     * @group portaluser
     * @group backend-portaluser
     */
    public function MothershipCancelPendingInvite(AcceptanceTester $I)
    {
        $I->amOnPage(self::PORTALUSERS_VIEW_ALL_URL);
        $I->waitForText("Pending invites (1)", 30, "#pendingInvites h2");

        $I->click("Cancel invite", self::PENDING_ROWS);
        $this->confirm($I);

        $I->waitForText("Invite cancelled.", 30, "#system-message-container");
        $I->dontSeeElement("#pendingInvites");
        $I->seeNumberOfElements(self::LIST_ROWS, 2);

        $I->dontSeeInDatabase("jos_mothership_users", ['id' => $this->pendingAccess['id']]);
        // The login stays; only the access goes.
        $I->seeInDatabase("jos_users", ['id' => $this->pendingData['id']]);
        $I->seeInDatabase("jos_mothership_logs", ['object_type' => 'user_access', 'object_id' => $this->pendingAccess['id'], 'action' => 'removed']);
    }

    /**
     * @group backend
     * @group portaluser
     * @group backend-portaluser
     */
    public function MothershipRemoveAllAccessFromList(AcceptanceTester $I)
    {
        $I->amOnPage(self::PORTALUSERS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Portal Users", 30, "h1.page-title");

        // Olive only owns clients, so there is nothing of hers to remove.
        $I->click(self::LIST_ROWS . ":nth-child(1) input[name='cid[]']");
        $I->click(self::LIST_ROWS . ":nth-child(2) input[name='cid[]']");
        $I->click("Actions", "#toolbar");
        $I->click("Remove all access", "#toolbar");
        $this->confirm($I);

        $I->waitForText("Removed portal access for 1 person.", 30, "#system-message-container");
        $I->see("1 selected person only owns clients", "#system-message-container");

        $I->dontSeeInDatabase("jos_mothership_users", ['id' => $this->grantedAccess['id']]);
        $I->seeInDatabase("jos_mothership_clients", ['id' => $this->clientData['id'], 'owner_user_id' => $this->ownerData['id']]);
    }

    /**
     * @group backend
     * @group portaluser
     * @group backend-portaluser
     */
    public function MothershipPortalUserPage(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::PORTALUSER_VIEW_URL, $this->grantedData['id']));
        $I->waitForText("Mothership: Portal User: Gary Granted", 30, "h1.page-title");
        $I->dontSee("Warning:");

        $I->takeFullPageScreenshot("admin-portal-user");

        $I->see("Administrator", "section[data-role=full_admin] header");
        $I->see("1 client", "section[data-role=full_admin] header");
        $I->see("Test Client", "section[data-role=full_admin] tbody tr");
        $I->see("Active", "section[data-role=full_admin] tbody tr");

        // Give access to a client they don't have yet. They already have a login, so it is
        // theirs straight away, and the notice names the owner.
        $I->selectOption("#grant-client", "Other Client");
        $I->click("Give access", "#grantAccess");
        $I->waitForText("Access given.", 30, "#system-message-container");
        $I->see("2 clients", "section[data-role=full_admin] header");

        $I->seeInDatabase("jos_mothership_users", [
            'user_id' => $this->grantedData['id'],
            'client_id' => $this->otherClientData['id'],
            'role' => 'full_admin',
            'invited_as' => $this->ownerData['id'],
        ]);
        $I->seeInDatabase("jos_mothership_emails", [
            'recipient' => $this->grantedData['email'],
            'template' => 'user.invite',
            'object_type' => 'user_access',
            'client_id' => $this->otherClientData['id'],
            'subject' => 'You now have access to Other Client',
        ]);

        // Nothing left to give.
        $I->see("This person already has access to every client.", "#grantAccess");

        // Take the first one away again.
        $I->click("Remove", "section[data-role=full_admin] tbody tr:nth-child(2)");
        $this->confirm($I);
        $I->waitForText("Access removed.", 30, "#system-message-container");
        $I->see("1 client", "section[data-role=full_admin] header");
        $I->dontSeeInDatabase("jos_mothership_users", ['id' => $this->grantedAccess['id']]);
    }

    /**
     * @group backend
     * @group portaluser
     * @group backend-portaluser
     */
    public function MothershipPortalUserPageOwnerAndPending(AcceptanceTester $I)
    {
        // Ownership is listed but is not changed here.
        $I->amOnPage(sprintf(self::PORTALUSER_VIEW_URL, $this->ownerData['id']));
        $I->waitForText("Mothership: Portal User: Olive Owner", 30, "h1.page-title");
        $I->see("2 clients", "section[data-role=owner] header");
        $I->see("Test Client", "section[data-role=owner]");
        $I->see("Other Client", "section[data-role=owner]");
        $I->dontSeeElement("section[data-role=owner] button");

        // A pending invite can be sent again, which replaces its link.
        $I->amOnPage(sprintf(self::PORTALUSER_VIEW_URL, $this->pendingData['id']));
        $I->waitForText("Mothership: Portal User: Penny Pending", 30, "h1.page-title");
        $I->see("Invite pending", "section[data-role=full_admin] tbody tr");
        $I->click("Resend invite", "section[data-role=full_admin] tbody tr");
        $I->waitForText("A new invite link was sent.", 30, "#system-message-container");

        $I->dontSeeInDatabase("jos_mothership_users", ['id' => $this->pendingAccess['id'], 'invite_token_hash' => $this->pendingAccess['invite_token_hash']]);
        $I->seeInDatabase("jos_mothership_emails", ['recipient' => $this->pendingData['email'], 'object_type' => 'user_access', 'object_id' => $this->pendingAccess['id'], 'status' => 'sent']);

        // Someone who doesn't exist goes back to the list.
        $I->amOnPage(sprintf(self::PORTALUSER_VIEW_URL, 999999));
        $I->waitForText("Mothership: Portal Users", 30, "h1.page-title");
        $I->see("That user doesn't exist.", "#system-message-container");
    }

    /**
     * @group backend
     * @group portaluser
     * @group backend-portaluser
     */
    public function MothershipClientUsersTab(AcceptanceTester $I)
    {
        $email = "nina.{$this->token}@mailinator.com";

        $I->amOnPage(sprintf(self::CLIENT_EDIT_URL, $this->clientData['id']));
        $I->waitForElement(self::USERS_TAB, 30);
        $I->click(self::USERS_TAB);
        $I->waitForText("Invite someone", 30, "#clientUsers");
        $I->dontSee("Warning:");

        $I->takeFullPageScreenshot("admin-client-users-tab");

        // The owner first, then everyone given access.
        $I->seeNumberOfElements(self::CLIENT_USER_ROWS, 3);
        $I->see("Olive Owner", self::CLIENT_USER_ROWS . ":nth-child(1)");
        $I->see("Owner", self::CLIENT_USER_ROWS . ":nth-child(1)");
        $I->dontSeeElement(self::CLIENT_USER_ROWS . ":nth-child(1) button");
        $I->see("Gary Granted", self::CLIENT_USER_ROWS . ":nth-child(2)");
        $I->see("Active", self::CLIENT_USER_ROWS . ":nth-child(2)");
        $I->see("Penny Pending", self::CLIENT_USER_ROWS . ":nth-child(3)");
        $I->see("Invite pending", self::CLIENT_USER_ROWS . ":nth-child(3)");

        // Invite someone with no login yet.
        $I->fillField("#access-name", "Nina New");
        $I->fillField("#access-email", $email);
        $I->scrollTo("#access-invite");
        $I->click("#access-invite");
        $I->waitForText("Invite sent to {$email}.", 30, "#system-message-container");

        // Back on the same client, where they are now waiting.
        $I->seeInCurrentUrl("view=client&layout=edit&id={$this->clientData['id']}");
        $I->click(self::USERS_TAB);
        $I->seeNumberOfElements(self::CLIENT_USER_ROWS, 4);
        $I->see("Nina New", self::CLIENT_USER_ROWS);

        $userId = $I->grabFromDatabase("jos_users", "id", ['email' => $email, 'username' => $email, 'name' => 'Nina New']);
        $I->seeInDatabase("jos_user_usergroup_map", ['user_id' => $userId, 'group_id' => 2]);
        $I->seeInDatabase("jos_mothership_users", [
            'user_id' => $userId,
            'client_id' => $this->clientData['id'],
            'role' => 'full_admin',
            'accepted' => null,
            'invited_as' => $this->ownerData['id'],
        ]);
        $I->seeInDatabase("jos_mothership_emails", [
            'recipient' => $email,
            'template' => 'user.invite',
            'object_type' => 'user_access',
            'subject' => "You're invited to the Test Client client portal",
            'reply_to' => $this->ownerData['email'],
            'status' => 'sent',
        ]);

        // The same address again is refused.
        $I->fillField("#access-name", "Nina New");
        $I->fillField("#access-email", $email);
        // Enter sends the invite too, rather than saving the client form around it.
        $I->pressKey("#access-email", \Facebook\WebDriver\WebDriverKeys::ENTER);
        $I->waitForText("That person already has access to this client.", 30, "#system-message-container");

        // Remove someone.
        $I->click(self::USERS_TAB);
        $I->click("Remove", self::CLIENT_USER_ROWS . ":nth-child(2)");
        $this->confirm($I);
        $I->waitForText("Access removed.", 30, "#system-message-container");
        $I->dontSeeInDatabase("jos_mothership_users", ['id' => $this->grantedAccess['id']]);
        $I->seeInDatabase("jos_users", ['id' => $this->grantedData['id']]);
    }
}
