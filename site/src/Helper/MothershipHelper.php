<?php
namespace TrevorBice\Component\Mothership\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;

class MothershipHelper
{
    /**
     * Get the client IDs for the current user.
     *
     * @param  int|null  $userId  The user ID (optional; defaults to the current user).
     * @return array|null  An array of client IDs the user owns, or null if none found.
     */
    public static function getUserClientIds($userId = null): ?array
    {
        // Determine user
        if ($userId === null) {
            $user = Factory::getApplication()->getIdentity();
            $userId = $user ? (int) $user->id : 0;
        } else {
            $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
        }

        // Bail out if not logged in or invalid
        if (!$user || $user->guest || !$userId) {
            return null;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__mothership_clients'))
            ->where($db->quoteName('owner_user_id') . ' = ' . (int) $userId);

        $db->setQuery($query);
        $results = $db->loadColumn(); // returns an array of all ids

        return !empty($results) ? array_map('intval', $results) : null;
    }
        
    public static function getClient($clientId): ?object
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__mothership_clients')
            ->where('id = ' . (int) $clientId);
        $db->setQuery($query);

        return $db->loadObject();
    }

    /** True when the current user is attached to more than one client. */
    public static function userHasMultipleClients(): bool
    {
        return \count(self::getUserClientIds() ?? []) > 1;
    }

    /**
     * Render a status as a coloured dot (optionally with a text label). Active/
     * online is green; inactive is grey; offline red; pending amber.
     */
    public static function statusDot(?string $status, bool $withLabel = true): string
    {
        $s = strtolower(trim((string) $status));
        $map = [
            'online'   => ['#1a9d4b', 'Online'],
            'active'   => ['#1a9d4b', 'Active'],
            '1'        => ['#1a9d4b', 'Active'],
            'offline'  => ['#c81e1e', 'Offline'],
            'inactive' => ['#9aa0a6', 'Inactive'],
            '0'        => ['#9aa0a6', 'Inactive'],
            'error'    => ['#e0a100', 'Error'],
            'unknown'  => ['#9aa0a6', 'Unknown'],
            'pending'  => ['#e0a100', 'Pending'],
        ];

        if (isset($map[$s])) {
            [$color, $label] = $map[$s];
        } elseif ($s === '') {
            [$color, $label] = ['#9aa0a6', '—'];
        } else {
            [$color, $label] = ['#9aa0a6', ucfirst($s)];
        }

        $dot = '<span class="ms-status-dot" style="background:' . $color . ';" title="'
            . htmlspecialchars($label) . '" aria-label="' . htmlspecialchars($label) . '"></span>';

        if (!$withLabel) {
            return $dot;
        }

        return '<span class="ms-status">' . $dot
            . '<span class="ms-status-label">' . htmlspecialchars($label) . '</span></span>';
    }

    /**
     * A project's live status string (metadata.status: online/offline/error/unknown).
     * This is the value the admin form sets and the scanner writes; use it for the
     * status dot everywhere so front end and back end stay consistent.
     */
    public static function projectScanStatus(object $project): string
    {
        $meta = self::projectMeta($project);

        return $meta ? (string) ($meta->status ?? '') : '';
    }

    /** A project's metadata as an object, or null. */
    private static function projectMeta(object $project): ?object
    {
        if (empty($project->metadata)) {
            return null;
        }
        $meta = \is_string($project->metadata) ? json_decode($project->metadata) : $project->metadata;

        // The single-project model hands the metadata over as an array.
        if (\is_array($meta)) {
            $meta = (object) $meta;
        }

        return \is_object($meta) ? $meta : null;
    }

    /**
     * Human label for a project's type. For a website this is the CMS and version
     * (e.g. "WordPress 6.8.3", or just "WordPress"); otherwise the type name.
     */
    public static function projectTypeLabel(object $project): string
    {
        $type = strtolower(trim((string) ($project->type ?? '')));

        if ($type === 'website') {
            $meta = self::projectMeta($project);
            $cms  = $meta ? trim((string) ($meta->cms_type ?? '')) : '';

            if ($cms !== '') {
                $known = [
                    'wordpress' => 'WordPress', 'joomla' => 'Joomla', 'drupal' => 'Drupal',
                    'shopify' => 'Shopify', 'wix' => 'Wix', 'squarespace' => 'Squarespace',
                ];
                $label = $known[strtolower($cms)] ?? $cms;
                $ver   = $meta ? trim((string) ($meta->cms_version ?? '')) : '';

                return htmlspecialchars(trim($label . ' ' . $ver));
            }

            return 'Website';
        }

        return $type !== '' ? htmlspecialchars(ucfirst($type)) : '—';
    }

    /** Registrable domain for a project (e.g. processes.org for ptitraining.processes.org), or null. */
    public static function projectDomain(object $project): ?string
    {
        $meta = self::projectMeta($project);
        $host = $meta ? trim((string) ($meta->primary_domain ?? '')) : '';
        if ($host === '') {
            return null;
        }

        return self::registrableDomain($host);
    }

    /**
     * Reduce a host to its registrable domain — the last two labels, keeping three
     * for common two-part public suffixes (co.uk, com.au, …). So a subdomain like
     * ptitraining.processes.org resolves to processes.org.
     */
    public static function registrableDomain(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('#^https?://#i', '', $host);
        $host = explode('/', $host)[0];            // strip any path
        $host = preg_replace('/:\d+$/', '', $host); // strip any port

        $labels = array_values(array_filter(explode('.', $host), 'strlen'));
        $n      = \count($labels);
        if ($n <= 2) {
            return implode('.', $labels);
        }

        $lastTwo  = $labels[$n - 2] . '.' . $labels[$n - 1];
        $twoParts = ['co.uk', 'org.uk', 'com.au', 'net.au', 'org.au', 'co.nz', 'co.za', 'com.br', 'co.jp', 'co.in', 'com.mx'];
        if (\in_array($lastTwo, $twoParts, true) && $n >= 3) {
            return $labels[$n - 3] . '.' . $lastTwo;
        }

        return $lastTwo;
    }

    /** Primary site URL for a website project (from metadata), or null. */
    public static function projectUrl(object $project): ?string
    {
        if (strtolower(trim((string) ($project->type ?? ''))) !== 'website') {
            return null;
        }
        $meta = self::projectMeta($project);
        $url  = $meta ? trim((string) ($meta->primary_url ?? '')) : '';

        return $url !== '' ? $url : null;
    }
}
