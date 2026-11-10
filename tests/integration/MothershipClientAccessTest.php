<?php

namespace Tests\Integration;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserHelper;
use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

/**
 * Portal users: who besides a client's owner can use its portal, and how they are invited.
 */
class MothershipClientAccessTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $ownerData;
    protected $staffData;
    protected $clientData;
    protected $otherClientData;

    /** Unique to this run, so Mailpit can be searched without picking up another test's mail. */
    protected string $token;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/LogHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/EmailLog.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/EmailService.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/ClientAccess.php';
        require_once JPATH_ROOT . '/components/com_mothership/src/Helper/MothershipHelper.php';

        $this->token = 'msaccess' . bin2hex(random_bytes(4));

        // An invite link is built from the site's address, which a command line run has none of.
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'joomla';

        // Saving a Joomla user runs the user plugins, whose classes the headless bootstrap
        // does not map.
        Factory::getApplication()->createExtensionNamespaceMap();

        // Grants are cached per request, and the whole suite is one request.
        ClientAccess::reset();

        $this->tester->setMothershipConfig([
            'company_name' => 'Your Company Name',
            'company_address' => '123 Nowhere St, CA, 12345',
            'company_address_1' => '123 Nowhere St',
            'company_address_2' => '',
            'company_city' => 'Nowhere',
            'company_state' => 'California',
            'company_zip' => '12345',
            'company_phone' => '555 555-5555',
        ]);

        $this->ownerData = $this->tester->createJoomlaUser([
            'name'     => 'Olive Owner',
            'username' => 'olive.' . $this->token,
            'email'    => "olive.{$this->token}@mailinator.com",
        ]);

        $this->staffData = $this->tester->createJoomlaUser([
            'name'     => 'Sam Staff',
            'username' => 'sam.' . $this->token,
            'email'    => "sam.{$this->token}@mailinator.com",
        ]);

        $this->clientData = $this->tester->createMothershipClient([
            'name'          => 'Access Client ' . $this->token,
            'owner_user_id' => $this->ownerData['id'],
        ]);

        $this->otherClientData = $this->tester->createMothershipClient([
            'name'          => 'Other Client ' . $this->token,
            'owner_user_id' => $this->staffData['id'],
        ]);
    }

    /** The token in an invite link. */
    private function tokenFrom(string $link): string
    {
        $this->assertMatchesRegularExpression('/[?&]token=[a-f0-9]{64}$/', $link);

        return substr($link, -64);
    }

    private function accessRow(int $accessId): array
    {
        $columns = ['user_id', 'client_id', 'role', 'created_by', 'invited_as', 'invite_token_hash', 'invite_expires', 'accepted', 'invite_sent_at', 'invite_error'];
        $row     = [];

        foreach ($columns as $column) {
            $row[$column] = $this->tester->grabFromDatabase('jos_mothership_users', $column, ['id' => $accessId]);
        }

        return $row;
    }

    public function testInvitingSomeoneNewCreatesALoginAndEmailsAOneTimeLink()
    {
        $email  = "new.{$this->token}@mailinator.com";
        $result = ClientAccess::invite((int) $this->clientData['id'], 'Nina New', $email, (int) $this->staffData['id']);

        $this->assertTrue($result['new_login']);
        $this->assertTrue($result['emailed'], (string) $result['error']);
        $this->assertNull($result['error']);

        // A Registered login, named by its email, that Joomla did not announce itself.
        $this->tester->seeInDatabase('jos_users', ['id' => $result['user_id'], 'username' => $email, 'email' => $email, 'name' => 'Nina New', 'block' => 0]);
        $this->tester->seeInDatabase('jos_user_usergroup_map', ['user_id' => $result['user_id'], 'group_id' => 2]);

        $row = $this->accessRow($result['access_id']);
        $this->assertSame(ClientAccess::ROLE_FULL, $row['role']);
        $this->assertEquals($this->staffData['id'], $row['created_by']);
        $this->assertNull($row['invited_as']);
        $this->assertNull($row['accepted'], 'the invite is pending until a password is set');
        $this->assertNotEmpty($row['invite_expires']);
        $this->assertNotEmpty($row['invite_sent_at'], 'the row records that the email left');
        $this->assertNull($row['invite_error']);

        // Only a hash of the token is kept, and it finds the invite.
        $token = $this->tokenFrom($result['link']);
        $this->assertSame(hash('sha256', $token), $row['invite_token_hash']);
        $this->assertSame($email, ClientAccess::findInvite($token)->email);

        // The email carries the link and is in the email log, tied to the access row.
        $subject = "You're invited to the {$this->clientData['name']} client portal";
        $message = $this->tester->getEmailBySubject($subject);
        $this->assertNotEmpty($message, 'Mailpit has the invite');

        $this->tester->seeInDatabase('jos_mothership_emails', [
            'recipient'   => $email,
            'subject'     => $subject,
            'template'    => 'user.invite',
            'object_type' => 'user_access',
            'object_id'   => $result['access_id'],
            'client_id'   => $this->clientData['id'],
            'status'      => 'sent',
        ]);

        $body = $this->tester->grabFromDatabase('jos_mothership_emails', 'body_html', ['subject' => $subject]);
        $this->assertStringContainsString('token=' . $token, $body);
        $this->assertStringContainsString('Sam Staff has invited you', $body);
        $this->assertStringContainsString('Hello Nina,', $body);

        $this->tester->seeInDatabase('jos_mothership_logs', [
            'object_type' => 'user_access',
            'object_id'   => $result['access_id'],
            'action'      => 'invited',
            'client_id'   => $this->clientData['id'],
            'user_id'     => $this->staffData['id'],
        ]);
    }

    public function testInvitingAnExistingLoginGivesAccessStraightAway()
    {
        $result = ClientAccess::invite((int) $this->clientData['id'], '', strtoupper($this->staffData['email']), (int) $this->ownerData['id']);

        $this->assertFalse($result['new_login']);
        $this->assertNull($result['link'], 'someone who already has a password gets no link');
        $this->assertEquals($this->staffData['id'], $result['user_id']);

        $row = $this->accessRow($result['access_id']);
        $this->assertNotEmpty($row['accepted']);
        $this->assertNull($row['invite_token_hash']);

        $this->assertNotEmpty($this->tester->getEmailBySubject("You now have access to {$this->clientData['name']}"));
        $this->assertSame(ClientAccess::ROLE_FULL, ClientAccess::roleFor((int) $this->clientData['id'], (int) $this->staffData['id']));
    }

    public function testAnInviteCanNameTheOwnerAsTheInviter()
    {
        $email  = "behalf.{$this->token}@mailinator.com";
        $result = ClientAccess::invite((int) $this->clientData['id'], 'Bea Behalf', $email, (int) $this->staffData['id'], (int) $this->ownerData['id']);

        $row = $this->accessRow($result['access_id']);
        $this->assertEquals($this->staffData['id'], $row['created_by'], 'who really sent it is still recorded');
        $this->assertEquals($this->ownerData['id'], $row['invited_as']);

        $this->tester->seeInDatabase('jos_mothership_emails', ['recipient' => $email, 'reply_to' => $this->ownerData['email']]);
        $body = $this->tester->grabFromDatabase('jos_mothership_emails', 'body_html', ['recipient' => $email]);
        $this->assertStringContainsString('Olive Owner has invited you', $body);

        // Only the client's owner can be named.
        $this->expectException(\RuntimeException::class);
        ClientAccess::invite((int) $this->clientData['id'], 'Someone Else', "else.{$this->token}@mailinator.com", (int) $this->ownerData['id'], (int) $this->staffData['id']);
    }

    /**
     * @dataProvider refusedInvites
     */
    public function testAnInviteIsRefused(string $who, string $name, string $expected)
    {
        ClientAccess::invite((int) $this->clientData['id'], '', $this->staffData['email'], (int) $this->ownerData['id']);

        $this->tester->createJoomlaUser([
            'name'     => 'Blocked Person',
            'username' => 'blocked.' . $this->token,
            'email'    => "blocked.{$this->token}@mailinator.com",
            'block'    => '1',
        ]);

        $emails = [
            'owner'    => $this->ownerData['email'],
            'again'    => $this->staffData['email'],
            'invalid'  => 'not-an-email',
            'nameless' => "nameless.{$this->token}@mailinator.com",
            'blocked'  => "blocked.{$this->token}@mailinator.com",
        ];

        try {
            ClientAccess::invite((int) $this->clientData['id'], $name, $emails[$who], (int) $this->ownerData['id']);
            $this->fail('The invite went through.');
        } catch (\RuntimeException $e) {
            $this->assertSame($expected, $e->getMessage());
        }
    }

    public function refusedInvites(): array
    {
        return [
            'the owner'               => ['owner', '', 'That person already owns this client.'],
            'someone who has access'  => ['again', '', 'That person already has access to this client.'],
            'not an email address'    => ['invalid', 'Nobody', 'Enter a valid email address.'],
            'a new login with no name' => ['nameless', '', 'Enter a name for the new user.'],
            'a disabled login'        => ['blocked', '', "The login for that email is disabled, so it can't be given access."],
        ];
    }

    public function testAcceptingAnInviteSetsThePasswordAndUsesUpTheLink()
    {
        $email  = "accept.{$this->token}@mailinator.com";
        $result = ClientAccess::invite((int) $this->clientData['id'], 'Ana Accept', $email, (int) $this->staffData['id']);
        $token  = $this->tokenFrom($result['link']);

        // A password the site would not accept leaves the invite as it was.
        foreach ([['Str0ng&Lengthy-Passw0rd', 'different'], ['short', 'short']] as [$password, $confirm]) {
            try {
                ClientAccess::acceptInvite($token, $password, $confirm);
                $this->fail('A bad password was accepted.');
            } catch (\RuntimeException $e) {
                $this->assertNotNull(ClientAccess::findInvite($token));
            }
        }

        $invite = ClientAccess::acceptInvite($token, 'Str0ng&Lengthy-Passw0rd', 'Str0ng&Lengthy-Passw0rd');
        $this->assertEquals($result['user_id'], $invite->user_id);
        $this->assertSame($this->clientData['name'], $invite->client_name);

        $row = $this->accessRow($result['access_id']);
        $this->assertNotEmpty($row['accepted']);
        $this->assertNull($row['invite_token_hash']);
        $this->assertNull($row['invite_expires']);

        $hash = $this->tester->grabFromDatabase('jos_users', 'password', ['id' => $result['user_id']]);
        $this->assertTrue(UserHelper::verifyPassword('Str0ng&Lengthy-Passw0rd', $hash));

        // The link works once.
        $this->assertNull(ClientAccess::findInvite($token));
        $this->tester->seeInDatabase('jos_mothership_logs', ['object_type' => 'user_access', 'object_id' => $result['access_id'], 'action' => 'accepted']);

        $this->expectException(\RuntimeException::class);
        ClientAccess::acceptInvite($token, 'Str0ng&Lengthy-Passw0rd', 'Str0ng&Lengthy-Passw0rd');
    }

    public function testAnExpiredOrUnknownLinkFindsNothing()
    {
        $result = ClientAccess::invite((int) $this->clientData['id'], 'Eve Expired', "expired.{$this->token}@mailinator.com", (int) $this->staffData['id']);
        $token  = $this->tokenFrom($result['link']);

        $this->tester->updateInDatabase('jos_mothership_users', ['invite_expires' => '2020-01-01 00:00:00'], ['id' => $result['access_id']]);

        $this->assertNull(ClientAccess::findInvite($token));
        $this->assertNull(ClientAccess::findInvite(str_repeat('a', 64)));
        $this->assertNull(ClientAccess::findInvite("' OR 1=1 -- "));

        $pending = array_values(array_filter(ClientAccess::pendingInvites(), fn ($invite) => (int) $invite->access_id === $result['access_id']));
        $this->assertCount(1, $pending);
        $this->assertTrue($pending[0]->invite_expired);
        $this->assertSame('Sam Staff', $pending[0]->sent_by);
    }

    public function testResendingAnInviteReplacesItsLink()
    {
        $result = ClientAccess::invite((int) $this->clientData['id'], 'Rae Resend', "resend.{$this->token}@mailinator.com", (int) $this->staffData['id']);
        $first  = $this->tokenFrom($result['link']);

        $again  = ClientAccess::resendInvite($result['access_id']);
        $second = $this->tokenFrom($again['link']);

        $this->assertTrue($again['emailed'], (string) $again['error']);
        $this->assertNotSame($first, $second);
        $this->assertNull(ClientAccess::findInvite($first), 'the earlier link stops working');
        $this->assertNotNull(ClientAccess::findInvite($second));
        $this->assertSame(2, $this->tester->grabNumRecords('jos_mothership_emails', ['object_type' => 'user_access', 'object_id' => $result['access_id']]));

        // Nothing to resend once it is accepted.
        ClientAccess::acceptInvite($second, 'Str0ng&Lengthy-Passw0rd', 'Str0ng&Lengthy-Passw0rd');
        $this->expectException(\RuntimeException::class);
        ClientAccess::resendInvite($result['access_id']);
    }

    public function testSigningInAcceptsPendingInvites()
    {
        $result = ClientAccess::invite((int) $this->clientData['id'], 'Lou Login', "login.{$this->token}@mailinator.com", (int) $this->staffData['id']);

        $this->assertSame(1, ClientAccess::acceptPendingForUser($result['user_id']));
        $this->assertSame(0, ClientAccess::acceptPendingForUser($result['user_id']));
        $this->assertNotEmpty($this->accessRow($result['access_id'])['accepted']);
        $this->assertSame(0, ClientAccess::acceptPendingForUser(0));
    }

    public function testThePortalShowsAClientToItsOwnerAndItsAdministrators()
    {
        $clientId = (int) $this->clientData['id'];
        $ownerId  = (int) $this->ownerData['id'];
        $staffId  = (int) $this->staffData['id'];

        $this->assertSame([$clientId], MothershipHelper::getUserClientIds($ownerId));
        $this->assertSame([(int) $this->otherClientData['id']], MothershipHelper::getUserClientIds($staffId));

        $result = ClientAccess::invite($clientId, '', $this->staffData['email'], $ownerId);

        $ids = MothershipHelper::getUserClientIds($staffId);
        sort($ids);
        $this->assertSame([$clientId, (int) $this->otherClientData['id']], $ids);
        $this->assertSame([$clientId], MothershipHelper::getUserClientIds($ownerId), 'access is one way');

        $members = ClientAccess::members($clientId);
        $this->assertSame(['Olive Owner', 'Sam Staff'], array_column($members, 'name'));
        $this->assertSame([ClientAccess::ROLE_OWNER, ClientAccess::ROLE_FULL], array_column($members, 'role'));

        $access = ClientAccess::personAccess($staffId);
        $this->assertSame([ClientAccess::ROLE_OWNER, ClientAccess::ROLE_FULL], array_column($access, 'role'));

        ClientAccess::remove($result['access_id'], $ownerId);

        $this->assertSame([(int) $this->otherClientData['id']], MothershipHelper::getUserClientIds($staffId));
        $this->tester->dontSeeInDatabase('jos_mothership_users', ['id' => $result['access_id']]);
        $this->tester->seeInDatabase('jos_users', ['id' => $staffId], 'the login stays');
        $this->tester->seeInDatabase('jos_mothership_logs', ['object_type' => 'user_access', 'object_id' => $result['access_id'], 'action' => 'removed', 'user_id' => $ownerId]);

        $this->expectException(\RuntimeException::class);
        ClientAccess::remove($result['access_id'], $ownerId);
    }

    public function testALevelThatIsNotInUseYetOpensNothing()
    {
        $this->tester->createMothershipUser([
            'user_id'   => $this->ownerData['id'],
            'client_id' => $this->otherClientData['id'],
            'role'      => ClientAccess::ROLE_BILLING,
        ]);

        ClientAccess::reset();

        $this->assertSame(ClientAccess::ROLE_BILLING, ClientAccess::roleFor((int) $this->otherClientData['id'], (int) $this->ownerData['id']));
        $this->assertSame([(int) $this->clientData['id']], MothershipHelper::getUserClientIds((int) $this->ownerData['id']));
        $this->assertNull(MothershipHelper::getUserClientIds(0));
    }

    public function testAPasswordIsCheckedAgainstTheSitesRules()
    {
        $this->assertNull(ClientAccess::passwordProblem('Str0ng&Lengthy-Passw0rd'));
        $this->assertSame(
            sprintf('Use at least %d characters.', ClientAccess::minimumPasswordLength()),
            ClientAccess::passwordProblem('short')
        );
        $this->assertGreaterThanOrEqual(8, ClientAccess::minimumPasswordLength());
    }
}
