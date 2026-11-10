<?php

namespace TrevorBice\Component\Mothership\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\Table\User as UserTable;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;

/**
 * Who can use a client's portal.
 *
 * A client's owner (clients.owner_user_id) has always seen it. Anyone else is given access with
 * a row in #__mothership_users: they are invited by email, set a password from a one-time link
 * when they have no login yet, and from then on see the client as the owner does.
 *
 * There is one level to give, Administrator (full_admin). The table can also hold account_admin
 * and billing_admin, which are narrower and not acted on yet: such a row is listed with its own
 * label and opens nothing in the portal, so access can never be wider than what was granted.
 */
class ClientAccess
{
    public const ROLE_OWNER   = 'owner';
    public const ROLE_FULL    = 'full_admin';
    public const ROLE_ACCOUNT = 'account_admin';
    public const ROLE_BILLING = 'billing_admin';

    /** How long an invite link works. */
    public const INVITE_DAYS = 7;

    /** Grants per user id for this request. */
    private static array $grants = [];

    public static function roleLabel(string $role): string
    {
        $keys = [
            self::ROLE_OWNER   => 'COM_MOTHERSHIP_ACCESS_ROLE_OWNER',
            self::ROLE_FULL    => 'COM_MOTHERSHIP_ACCESS_ROLE_FULL_ADMIN',
            self::ROLE_ACCOUNT => 'COM_MOTHERSHIP_ACCESS_ROLE_ACCOUNT_ADMIN',
            self::ROLE_BILLING => 'COM_MOTHERSHIP_ACCESS_ROLE_BILLING_ADMIN',
        ];

        return isset($keys[$role]) ? self::text($keys[$role]) : ucfirst(str_replace('_', ' ', $role));
    }

    public static function roleDescription(string $role): string
    {
        $keys = [
            self::ROLE_OWNER   => 'COM_MOTHERSHIP_ACCESS_ROLE_OWNER_DESC',
            self::ROLE_FULL    => 'COM_MOTHERSHIP_ACCESS_ROLE_FULL_ADMIN_DESC',
            self::ROLE_ACCOUNT => 'COM_MOTHERSHIP_ACCESS_ROLE_RESERVED_DESC',
            self::ROLE_BILLING => 'COM_MOTHERSHIP_ACCESS_ROLE_RESERVED_DESC',
        ];

        return isset($keys[$role]) ? self::text($keys[$role]) : '';
    }

    /** Forget cached grants, e.g. after access changes. */
    public static function reset(): void
    {
        self::$grants = [];
    }

    /**
     * Every grant a user holds, keyed by client id: [client_id => ['role' => string,
     * 'access_id' => int|null]], where access_id is null for ownership.
     *
     * A pending invite counts. It belongs to a login whose password is random until the invite
     * link (or a password reset to the same address) sets one, so nobody can be signed in on it
     * without having proved they own the address.
     */
    public static function grants(?int $userId = null): array
    {
        $userId = $userId ?? self::currentUserId();

        if ($userId <= 0) {
            return [];
        }

        if (isset(self::$grants[$userId])) {
            return self::$grants[$userId];
        }

        $db     = self::db();
        $grants = [];

        $owned = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__mothership_clients'))
                ->where($db->quoteName('owner_user_id') . ' = :uid')
                ->bind(':uid', $userId, ParameterType::INTEGER)
        )->loadColumn();

        foreach ($owned as $clientId) {
            $grants[(int) $clientId] = ['role' => self::ROLE_OWNER, 'access_id' => null];
        }

        $rows = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'client_id', 'role']))
                ->from($db->quoteName('#__mothership_users'))
                ->where($db->quoteName('user_id') . ' = :uid')
                ->bind(':uid', $userId, ParameterType::INTEGER)
        )->loadObjectList();

        foreach ($rows as $row) {
            // The owner already has everything.
            if (!isset($grants[(int) $row->client_id])) {
                $grants[(int) $row->client_id] = ['role' => (string) $row->role, 'access_id' => (int) $row->id];
            }
        }

        return self::$grants[$userId] = $grants;
    }

    /** The clients a user sees in the portal: the ones they own or administer. */
    public static function clientIds(?int $userId = null): array
    {
        $ids = [];

        foreach (self::grants($userId) as $clientId => $grant) {
            if (\in_array($grant['role'], [self::ROLE_OWNER, self::ROLE_FULL], true)) {
                $ids[] = $clientId;
            }
        }

        return $ids;
    }

    /** owner, full_admin, ... or null when the user has nothing to do with the client. */
    public static function roleFor(int $clientId, ?int $userId = null): ?string
    {
        return self::grants($userId)[$clientId]['role'] ?? null;
    }

    /**
     * The owner first, then everyone given access. Each member has access_id (null for the
     * owner), user_id, name, email, role, pending, invite_expired, invite_sent_at and
     * invite_error.
     */
    public static function members(int $clientId): array
    {
        $db = self::db();

        $owner = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['u.id', 'u.name', 'u.email']))
                ->from($db->quoteName('#__mothership_clients', 'c'))
                ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = c.owner_user_id')
                ->where('c.id = :client')
                ->bind(':client', $clientId, ParameterType::INTEGER)
        )->loadObject();

        $members = [];

        if ($owner) {
            $members[] = (object) [
                'access_id'      => null,
                'user_id'        => (int) $owner->id,
                'name'           => (string) $owner->name,
                'email'          => (string) $owner->email,
                'role'           => self::ROLE_OWNER,
                'pending'        => false,
                'invite_expired' => false,
                'invite_sent_at' => null,
                'invite_error'   => null,
            ];
        }

        $rows = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['mu.id', 'mu.user_id', 'mu.role', 'mu.accepted', 'mu.invite_expires', 'mu.invite_sent_at', 'mu.invite_error', 'u.name', 'u.email']))
                ->from($db->quoteName('#__mothership_users', 'mu'))
                ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = mu.user_id')
                ->where('mu.client_id = :client')
                ->order('u.name ASC, mu.id ASC')
                ->bind(':client', $clientId, ParameterType::INTEGER)
        )->loadObjectList();

        $now = Factory::getDate()->toSql();

        foreach ($rows as $row) {
            // Someone who owns the client is listed once, as its owner.
            if ($owner && (int) $row->user_id === (int) $owner->id) {
                continue;
            }

            $members[] = (object) [
                'access_id'      => (int) $row->id,
                'user_id'        => (int) $row->user_id,
                'name'           => (string) $row->name,
                'email'          => (string) $row->email,
                'role'           => (string) $row->role,
                'pending'        => $row->accepted === null,
                'invite_expired' => $row->accepted === null && $row->invite_expires !== null && $row->invite_expires < $now,
                'invite_sent_at' => $row->invite_sent_at,
                'invite_error'   => $row->invite_error,
            ];
        }

        return $members;
    }

    /**
     * Everything one person can reach, ownership included, ordered by client name. Each entry
     * has access_id (null for ownership), client_id, client_name, role, pending and
     * invite_expired.
     */
    public static function personAccess(int $userId): array
    {
        $db  = self::db();
        $now = Factory::getDate()->toSql();
        $out = [];

        $owned = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'name']))
                ->from($db->quoteName('#__mothership_clients'))
                ->where($db->quoteName('owner_user_id') . ' = :uid')
                ->order($db->quoteName('name') . ' ASC')
                ->bind(':uid', $userId, ParameterType::INTEGER)
        )->loadObjectList('id');

        foreach ($owned as $client) {
            $out[] = (object) [
                'access_id'      => null,
                'client_id'      => (int) $client->id,
                'client_name'    => (string) $client->name,
                'role'           => self::ROLE_OWNER,
                'pending'        => false,
                'invite_expired' => false,
            ];
        }

        $rows = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['mu.id', 'mu.client_id', 'mu.role', 'mu.accepted', 'mu.invite_expires']))
                ->select($db->quoteName('c.name', 'client_name'))
                ->from($db->quoteName('#__mothership_users', 'mu'))
                ->join('INNER', $db->quoteName('#__mothership_clients', 'c') . ' ON c.id = mu.client_id')
                ->where('mu.user_id = :uid')
                ->order('c.name ASC')
                ->bind(':uid', $userId, ParameterType::INTEGER)
        )->loadObjectList();

        foreach ($rows as $row) {
            if (isset($owned[(int) $row->client_id])) {
                continue;
            }

            $out[] = (object) [
                'access_id'      => (int) $row->id,
                'client_id'      => (int) $row->client_id,
                'client_name'    => (string) $row->client_name,
                'role'           => (string) $row->role,
                'pending'        => $row->accepted === null,
                'invite_expired' => $row->accepted === null && $row->invite_expires !== null && $row->invite_expires < $now,
            ];
        }

        return $out;
    }

    /**
     * Invites nobody has accepted yet, newest first, with the person, client, who sent it and
     * who it was sent on behalf of. Each has invite_expired set when its link no longer works.
     */
    public static function pendingInvites(): array
    {
        $db   = self::db();
        $now  = Factory::getDate()->toSql();
        $rows = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['mu.user_id', 'mu.client_id', 'mu.role', 'mu.created', 'mu.invite_expires', 'mu.invite_error', 'u.name', 'u.email']))
                ->select($db->quoteName('mu.id', 'access_id'))
                ->select($db->quoteName('c.name', 'client_name'))
                ->select($db->quoteName('sender.name', 'sent_by'))
                ->select($db->quoteName('owner.name', 'on_behalf_of'))
                ->from($db->quoteName('#__mothership_users', 'mu'))
                ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = mu.user_id')
                ->join('INNER', $db->quoteName('#__mothership_clients', 'c') . ' ON c.id = mu.client_id')
                ->join('LEFT', $db->quoteName('#__users', 'sender') . ' ON sender.id = mu.created_by')
                ->join('LEFT', $db->quoteName('#__users', 'owner') . ' ON owner.id = mu.invited_as')
                ->where('mu.accepted IS NULL')
                ->order('mu.created DESC, mu.id DESC')
        )->loadObjectList();

        foreach ($rows as $row) {
            $row->invite_expired = $row->invite_expires !== null && $row->invite_expires < $now;
        }

        return $rows;
    }

    /** A person's access rows across all clients (not ownership, which lives on the client). */
    public static function accessRowsForUser(int $userId): array
    {
        $db = self::db();

        return $db->setQuery(
            $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__mothership_users'))
                ->where($db->quoteName('user_id') . ' = :uid')
                ->order($db->quoteName('id') . ' ASC')
                ->bind(':uid', $userId, ParameterType::INTEGER)
        )->loadObjectList();
    }

    /** An access row, or an exception when it doesn't exist. */
    public static function accessRow(int $accessId): object
    {
        $db  = self::db();
        $row = $db->setQuery(
            $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__mothership_users'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $accessId, ParameterType::INTEGER)
        )->loadObject();

        if (!$row) {
            throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_NO_LONGER'));
        }

        return $row;
    }

    /**
     * Give someone access to a client. When no login exists for the email, one is created and
     * they're emailed a link to set a password; an existing login gets access straight away and
     * is told so.
     *
     * $onBehalfOf names someone else as the inviter, so staff can invite for a client's owner:
     * the email names the owner and replies go to them, while created_by still records who sent
     * it. Only the client's owner can be named.
     *
     * @return array{access_id: int, user_id: int, new_login: bool, emailed: bool, link: ?string, error: ?string}
     *
     * @throws \RuntimeException with a message that can be shown to the person inviting
     */
    public static function invite(int $clientId, string $name, string $email, ?int $byUserId = null, ?int $onBehalfOf = null): array
    {
        $db     = self::db();
        $name   = trim($name);
        $email  = trim($email);
        $client = self::client($clientId);

        if ($onBehalfOf !== null && $onBehalfOf !== (int) $client->owner_user_id) {
            throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_ON_BEHALF'));
        }

        if (!MailHelper::isEmailAddress($email)) {
            throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_EMAIL'));
        }

        $userId = (int) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__users'))
                ->where('LOWER(' . $db->quoteName('email') . ') = LOWER(:email)')
                ->bind(':email', $email)
        )->loadResult();

        $newLogin = false;

        if ($userId) {
            if ($userId === (int) $client->owner_user_id) {
                throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_IS_OWNER'));
            }

            if (isset(self::grants($userId)[$clientId])) {
                throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_HAS_ACCESS'));
            }

            if ((int) self::user($userId)->block === 1) {
                throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_BLOCKED'));
            }
        } else {
            if ($name === '') {
                throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_NAME'));
            }

            $userId   = self::createLogin($name, $email);
            $newLogin = true;
        }

        $token = $newLogin ? bin2hex(random_bytes(32)) : null;
        $now   = Factory::getDate()->toSql();

        $row = (object) [
            'user_id'           => $userId,
            'client_id'         => $clientId,
            'role'              => self::ROLE_FULL,
            'all_accounts'      => 1,
            'created'           => $now,
            'created_by'        => ($byUserId ?? self::currentUserId()) ?: null,
            'invited_as'        => $onBehalfOf,
            'invite_token_hash' => $token !== null ? hash('sha256', $token) : null,
            'invite_expires'    => $token !== null ? Factory::getDate('+' . self::INVITE_DAYS . ' days')->toSql() : null,
            'accepted'          => $token !== null ? null : $now,
        ];

        $db->insertObject('#__mothership_users', $row, 'id');
        self::reset();

        $delivery = self::deliverInvite($row, $client, $email, $token);

        LogHelper::log([
            'client_id'   => $clientId,
            'object_type' => 'user_access',
            'object_id'   => (int) $row->id,
            'action'      => 'invited',
            'meta'        => ['user_id' => $userId, 'role' => $row->role, 'new_login' => $newLogin, 'emailed' => $delivery['ok'], 'on_behalf_of' => $onBehalfOf],
            'user_id'     => (int) $row->created_by,
        ]);

        return [
            'access_id' => (int) $row->id,
            'user_id'   => $userId,
            'new_login' => $newLogin,
            'emailed'   => $delivery['ok'],
            'link'      => $token !== null ? self::inviteLink($token) : null,
            'error'     => $delivery['error'],
        ];
    }

    /** Take someone's access away. Their login stays, so it can still reach other clients. */
    public static function remove(int $accessId, ?int $byUserId = null): void
    {
        $row = self::accessRow($accessId);
        $db  = self::db();

        $db->setQuery(
            $db->getQuery(true)
                ->delete($db->quoteName('#__mothership_users'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $accessId, ParameterType::INTEGER)
        )->execute();

        self::reset();

        LogHelper::log([
            'client_id'   => (int) $row->client_id,
            'object_type' => 'user_access',
            'object_id'   => $accessId,
            'action'      => 'removed',
            'meta'        => ['user_id' => (int) $row->user_id, 'role' => $row->role],
            'user_id'     => (int) ($byUserId ?? self::currentUserId()),
        ]);
    }

    /**
     * Send a fresh invite link to someone who hasn't set their password yet. The link sent
     * before stops working.
     *
     * @return array{emailed: bool, link: string, error: ?string}
     */
    public static function resendInvite(int $accessId): array
    {
        $row = self::accessRow($accessId);

        if ($row->accepted !== null) {
            throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_ALREADY_ACCEPTED'));
        }

        $token  = bin2hex(random_bytes(32));
        $update = (object) [
            'id'                => (int) $row->id,
            'invite_token_hash' => hash('sha256', $token),
            'invite_expires'    => Factory::getDate('+' . self::INVITE_DAYS . ' days')->toSql(),
        ];

        self::db()->updateObject('#__mothership_users', $update, 'id');

        $delivery = self::deliverInvite($row, self::client((int) $row->client_id), (string) self::user((int) $row->user_id)->email, $token);

        return ['emailed' => $delivery['ok'], 'link' => self::inviteLink($token), 'error' => $delivery['error']];
    }

    /** The one-time link an invite email carries. */
    public static function inviteLink(string $token): string
    {
        return rtrim(Uri::root(), '/') . '/index.php?option=com_mothership&view=invite&token=' . $token;
    }

    /** The pending invite for a token (with name, email, username, client_name), or null. */
    public static function findInvite(string $token): ?object
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $db   = self::db();
        $hash = hash('sha256', $token);
        $now  = Factory::getDate()->toSql();

        return $db->setQuery(
            $db->getQuery(true)
                ->select('mu.*')
                ->select($db->quoteName(['u.name', 'u.email', 'u.username']))
                ->select($db->quoteName('c.name', 'client_name'))
                ->from($db->quoteName('#__mothership_users', 'mu'))
                ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = mu.user_id')
                ->join('INNER', $db->quoteName('#__mothership_clients', 'c') . ' ON c.id = mu.client_id')
                ->where('mu.invite_token_hash = :hash')
                ->where('mu.accepted IS NULL')
                ->where('mu.invite_expires > :now')
                ->where('u.block = 0')
                ->bind(':hash', $hash)
                ->bind(':now', $now)
        )->loadObject() ?: null;
    }

    /**
     * Set the invited person's password and mark the invite used.
     *
     * @return object the invite, as findInvite() gives it
     *
     * @throws \RuntimeException when the link is no longer valid or the password is not accepted
     */
    public static function acceptInvite(string $token, string $password, string $confirm): object
    {
        $invite = self::findInvite($token);

        if (!$invite) {
            throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_LINK_EXPIRED'));
        }

        if ($password !== $confirm) {
            throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_PASSWORD_MISMATCH'));
        }

        if ($problem = self::passwordProblem($password)) {
            throw new \RuntimeException($problem);
        }

        $user = self::user((int) $invite->user_id);
        $data = ['password' => $password, 'password2' => $confirm];

        if (!$user->bind($data) || !$user->save(true)) {
            throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_PASSWORD_NOT_SAVED'));
        }

        self::markAccepted((int) $invite->user_id, 'invite');

        return $invite;
    }

    /**
     * Someone with a pending invite who has signed in has a password, however they got it: the
     * invite link, or Joomla's own password reset. Their invites are accepted at that point, so
     * the people screens stop saying "pending" about an active user.
     *
     * @return int How many invites were marked accepted.
     */
    public static function acceptPendingForUser(int $userId): int
    {
        return $userId > 0 ? self::markAccepted($userId, 'login') : 0;
    }

    /** Why a password isn't allowed under the site's com_users rules, or null when it is. */
    public static function passwordProblem(string $password): ?string
    {
        $params = ComponentHelper::getParams('com_users');
        $rules  = [
            [self::minimumPasswordLength(), mb_strlen($password), 'COM_MOTHERSHIP_ACCESS_PASSWORD_LENGTH'],
            [(int) $params->get('minimum_integers', 0), preg_match_all('/\d/', $password), 'COM_MOTHERSHIP_ACCESS_PASSWORD_INTEGERS'],
            [(int) $params->get('minimum_symbols', 0), preg_match_all('/[^\p{L}\d\s]/u', $password), 'COM_MOTHERSHIP_ACCESS_PASSWORD_SYMBOLS'],
            [(int) $params->get('minimum_uppercase', 0), preg_match_all('/\p{Lu}/u', $password), 'COM_MOTHERSHIP_ACCESS_PASSWORD_UPPERCASE'],
            [(int) $params->get('minimum_lowercase', 0), preg_match_all('/\p{Ll}/u', $password), 'COM_MOTHERSHIP_ACCESS_PASSWORD_LOWERCASE'],
        ];

        foreach ($rules as [$needed, $have, $key]) {
            if ($have < $needed) {
                return self::text($key, $needed);
            }
        }

        return null;
    }

    /** The shortest password the site's com_users rules allow, and never fewer than 8. */
    public static function minimumPasswordLength(): int
    {
        return max(8, (int) ComponentHelper::getParams('com_users')->get('minimum_length', 12));
    }

    /**
     * Accepting one invite activates every pending invite for the login: they all point at the
     * same password.
     */
    private static function markAccepted(int $userId, string $via): int
    {
        $db      = self::db();
        $pending = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'client_id']))
                ->from($db->quoteName('#__mothership_users'))
                ->where($db->quoteName('user_id') . ' = :uid')
                ->where($db->quoteName('accepted') . ' IS NULL')
                ->bind(':uid', $userId, ParameterType::INTEGER)
        )->loadObjectList();

        if (!$pending) {
            return 0;
        }

        $now = Factory::getDate()->toSql();

        $db->setQuery(
            $db->getQuery(true)
                ->update($db->quoteName('#__mothership_users'))
                ->set($db->quoteName('accepted') . ' = :now')
                ->set($db->quoteName('invite_token_hash') . ' = NULL')
                ->set($db->quoteName('invite_expires') . ' = NULL')
                ->where($db->quoteName('user_id') . ' = :uid')
                ->where($db->quoteName('accepted') . ' IS NULL')
                ->bind(':now', $now)
                ->bind(':uid', $userId, ParameterType::INTEGER)
        )->execute();

        foreach ($pending as $row) {
            LogHelper::log([
                'client_id'   => (int) $row->client_id,
                'object_type' => 'user_access',
                'object_id'   => (int) $row->id,
                'action'      => 'accepted',
                'meta'        => ['user_id' => $userId, 'via' => $via],
                'user_id'     => $userId,
            ]);
        }

        self::reset();

        return \count($pending);
    }

    private static function client(int $clientId): object
    {
        $db     = self::db();
        $client = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'name', 'owner_user_id']))
                ->from($db->quoteName('#__mothership_clients'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $clientId, ParameterType::INTEGER)
        )->loadObject();

        if (!$client) {
            throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_NO_CLIENT'));
        }

        return $client;
    }

    /**
     * Creates a Registered login with a random password; the invite link sets the real one.
     *
     * Saved through the user table rather than User::save() so Joomla's user plugin doesn't send
     * its own "account created" email (it does from the administrator, with the random password
     * in it). The invite email is the only one the person gets.
     */
    private static function createLogin(string $name, string $email): int
    {
        $db       = self::db();
        $username = $email;
        $suffix   = 1;

        while ((int) $db->setQuery(
            $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('username') . ' = ' . $db->quote($username))
        )->loadResult()) {
            $username = $email . '-' . ++$suffix;
        }

        $table = new UserTable($db);
        $data  = [
            'name'         => $name,
            'username'     => $username,
            'email'        => $email,
            'password'     => UserHelper::hashPassword(UserHelper::genRandomPassword(32)),
            'block'        => 0,
            'sendEmail'    => 0,
            'registerDate' => Factory::getDate()->toSql(),
            'params'       => '{}',
            'groups'       => [(int) ComponentHelper::getParams('com_users')->get('new_usertype', 2)],
        ];

        if (!$table->bind($data) || !$table->check() || !$table->store()) {
            throw new \RuntimeException(self::text('COM_MOTHERSHIP_ACCESS_ERROR_LOGIN_NOT_CREATED'));
        }

        return (int) $table->id;
    }

    /**
     * Send the invite and record on the access row whether it left the site. A lost invite then
     * shows on the people screens instead of leaving no trace.
     *
     * @return array{ok: bool, error: ?string}
     */
    private static function deliverInvite(object $access, object $client, string $email, ?string $token): array
    {
        $isNew     = $token !== null;
        $inviterId = (int) ($access->invited_as ?? 0) ?: (int) ($access->created_by ?? 0);
        $inviter   = $inviterId ? self::user($inviterId) : null;

        // For the email log: the invite is about this access row, on this client.
        $options = [
            'object_type' => 'user_access',
            'object_id'   => (int) $access->id,
            'client_id'   => (int) $client->id,
        ];

        if (!empty($access->invited_as) && $inviter && $inviter->email) {
            $options += ['reply_to' => $inviter->email, 'reply_to_name' => $inviter->name];
        }

        try {
            $ok = EmailService::sendTemplate(
                'user.invite',
                $email,
                self::text($isNew ? 'COM_MOTHERSHIP_ACCESS_EMAIL_SUBJECT_INVITE' : 'COM_MOTHERSHIP_ACCESS_EMAIL_SUBJECT_ACCESS', $client->name),
                [
                    'name'        => (string) self::user((int) $access->user_id)->name,
                    'client_name' => (string) $client->name,
                    'role_label'  => self::roleLabel((string) $access->role),
                    'role_desc'   => self::roleDescription((string) $access->role),
                    'inviter'     => $inviter ? (string) $inviter->name : '',
                    'link'        => $isNew ? self::inviteLink($token) : rtrim(Uri::root(), '/') . '/index.php?option=com_mothership&view=clients',
                    'is_new'      => $isNew,
                    'days'        => self::INVITE_DAYS,
                ],
                $options
            );

            $error = $ok ? null : (EmailService::lastError() ?: EmailService::REFUSED);
        } catch (\Throwable $e) {
            $ok    = false;
            $error = $e->getMessage() ?: \get_class($e);
        }

        $stamp = (object) [
            'id'             => (int) $access->id,
            'invite_sent_at' => $ok ? Factory::getDate()->toSql() : null,
            'invite_error'   => $ok ? null : mb_substr(trim((string) $error), 0, 255),
        ];

        self::db()->updateObject('#__mothership_users', $stamp, 'id', true);

        return ['ok' => (bool) $ok, 'error' => $error];
    }

    /**
     * A string from the administrator language file, which is where every string here lives:
     * this class also runs in the portal, where only the site file is loaded.
     */
    private static function text(string $key, ...$args): string
    {
        static $loaded = false;

        if (!$loaded) {
            $loaded = true;
            Factory::getApplication()->getLanguage()->load('com_mothership', JPATH_ADMINISTRATOR);
        }

        return $args ? Text::sprintf($key, ...$args) : Text::_($key);
    }

    private static function user(int $userId): User
    {
        return Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
    }

    private static function currentUserId(): int
    {
        $user = Factory::getApplication()->getIdentity();

        return ($user && !$user->guest) ? (int) $user->id : 0;
    }

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }
}
