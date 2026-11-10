<?php
/**
 * Give a user of the test site a Joomla API token and print it.
 *
 *   php tests/postman/token.php [username]     (inside the joomla container)
 *
 * This is what "Joomla API Token" on the user's profile does: plg_user_token stores a
 * random seed in the profile, and the token is that seed signed with the site secret.
 * Only for the throwaway test stack; a real site issues tokens from the user profile.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

define('_JEXEC', 1);
require dirname(__DIR__, 2) . '/configuration.php';

$config   = new JConfig();
$username = $argv[1] ?? 'admin';
$db       = new mysqli($config->host, $config->user, $config->password, $config->db);
$prefix   = $config->dbprefix;

$stmt = $db->prepare("SELECT id FROM {$prefix}users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$userId = $stmt->get_result()->fetch_column();

if (!$userId) {
    fwrite(STDERR, "token: no user named $username\n");
    exit(1);
}

$seed = random_bytes(32);

$db->query("DELETE FROM {$prefix}user_profiles WHERE user_id = " . (int) $userId . " AND profile_key LIKE 'joomlatoken.%'");
$stmt = $db->prepare("INSERT INTO {$prefix}user_profiles (user_id, profile_key, profile_value, ordering) VALUES (?, 'joomlatoken.token', ?, 1), (?, 'joomlatoken.enabled', '1', 2)");
$encoded = base64_encode($seed);
$stmt->bind_param('isi', $userId, $encoded, $userId);
$stmt->execute();

echo base64_encode('sha256:' . $userId . ':' . hash_hmac('sha256', $seed, $config->secret)), "\n";
