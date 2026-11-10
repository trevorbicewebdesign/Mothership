<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

$project = $this->item;
$meta    = is_array($project->metadata) ? $project->metadata : [];

$created    = $project->created ? date('M j, Y', strtotime($project->created)) : '—';
$type       = trim((string) ($project->type ?? ''));
$primaryUrl = trim((string) ($meta['primary_url'] ?? ''));
$domain     = trim((string) ($meta['primary_domain'] ?? ''));
$cmsType    = trim((string) ($meta['cms_type'] ?? ''));
$cmsVersion = trim((string) ($meta['cms_version'] ?? ''));
$siteStatus = strtolower(trim((string) ($meta['status'] ?? '')));
$staging    = trim((string) ($meta['staging_url'] ?? ''));
$dev        = trim((string) ($meta['dev_url'] ?? ''));
$favicon    = trim((string) ($meta['favicon_url'] ?? ''));
$screenshot = trim((string) ($meta['screenshot_url'] ?? ''));

// Friendly platform label (+ version).
$platform = '';
if ($cmsType !== '') {
    $known    = ['wordpress' => 'WordPress', 'joomla' => 'Joomla', 'drupal' => 'Drupal', 'shopify' => 'Shopify', 'wix' => 'Wix', 'squarespace' => 'Squarespace'];
    $platform = $known[strtolower($cmsType)] ?? $cmsType;
    if ($cmsVersion !== '') {
        $platform .= ' ' . $cmsVersion;
    }
}

// Admin deep-link based on the platform.
$adminUrl = '';
if ($primaryUrl !== '') {
    $base = rtrim($primaryUrl, '/');
    $adminUrl = strtolower($cmsType) === 'wordpress' ? $base . '/wp-admin'
        : (strtolower($cmsType) === 'joomla' ? $base . '/administrator' : $base);
}

// Site up/down pill.
$sitePill = '';
if ($siteStatus !== '') {
    $sm = $siteStatus === 'online' ? ['#e6f4ea', '#137333'] : ($siteStatus === 'offline' ? ['#fdecea', '#c81e1e'] : ['#eef0f2', '#5a626c']);
    $sitePill = '<span class="ms-pill" style="background:' . $sm[0] . ';color:' . $sm[1] . ';">' . htmlspecialchars(ucfirst($siteStatus)) . '</span>';
}

// The domain record for this site (registration/hosting live in Mothership too).
$domainRow = null;
if ($domain !== '') {
    $db = Factory::getContainer()->get('DatabaseDriver');
    $db->setQuery(
        $db->getQuery(true)->select('*')->from($db->quoteName('#__mothership_domains'))
            ->where($db->quoteName('name') . ' = ' . $db->quote($domain))
            ->where($db->quoteName('client_id') . ' = ' . (int) $project->client_id)
            ->setLimit(1)
    );
    $domainRow = $db->loadObject();
}

// Hosting facts (from metadata; a hosting record type can back this later).
$hosting = [];
foreach ([
    'hosting_provider' => 'Provider', 'hosting_plan' => 'Plan', 'server_ip' => 'Server IP',
    'php_version' => 'PHP version', 'hosting_url' => 'Control panel',
] as $k => $label) {
    $v = trim((string) ($meta[$k] ?? ''));
    if ($v !== '') {
        $hosting[$label] = $v;
    }
}

// Plugin list (from metadata; supports {name:version} or [{name,version}]).
$pluginRows = [];
if (!empty($meta['plugins']) && is_array($meta['plugins'])) {
    foreach ($meta['plugins'] as $k => $v) {
        if (is_array($v) || is_object($v)) {
            $v = (array) $v;
            $pluginRows[] = ['name' => (string) ($v['name'] ?? $k), 'version' => (string) ($v['version'] ?? '')];
        } else {
            $pluginRows[] = ['name' => (string) $k, 'version' => (string) $v];
        }
    }
}

$fmtDate   = fn ($d) => (!empty($d) && $d !== '0000-00-00 00:00:00') ? date('M j, Y', strtotime($d)) : '—';
$prettyUrl = fn ($u) => htmlspecialchars(preg_replace('#^https?://#i', '', rtrim($u, '/')));
$link      = fn ($u) => '<a href="' . htmlspecialchars($u) . '" target="_blank" rel="noopener">' . $prettyUrl($u) . '</a>';
?>
<style>
.ms-status { display:inline-flex; align-items:center; gap:7px; }
.ms-status-dot { display:inline-block; width:10px; height:10px; border-radius:50%; vertical-align:middle; flex:0 0 auto; }
.ms-status-label { font-size:13.5px; }
.ms-pill { display:inline-block; padding:2px 10px; border-radius:100px; font-size:12px; font-weight:600; white-space:nowrap; }
.ms-psection { border:1px solid #e0e0e0; border-radius:10px; padding:18px 22px; }
.ms-psection__title { font-size:17px; font-weight:700; margin:0 0 12px; }
.ms-proj-meta { display:flex; flex-wrap:wrap; align-items:center; gap:10px; color:#6b7480; font-size:14px; margin:0 0 20px; }
.ms-proj-meta .sep { color:#c3c7cc; }
.ms-proj-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; align-items:start; max-width:1180px; }
.ms-proj-main, .ms-proj-side { display:flex; flex-direction:column; gap:18px; min-width:0; }
.ms-proj-facts { display:grid; grid-template-columns:max-content 1fr; gap:10px 18px; margin:6px 0 18px; }
.ms-proj-facts dt { color:#6b7480; font-size:13.5px; font-weight:400; }
.ms-proj-facts dd { margin:0; font-size:14px; word-break:break-word; }
.ms-proj-actions { display:flex; flex-wrap:wrap; gap:10px; }
.ms-proj-shot { display:block; width:100%; border-radius:8px; border:1px solid #e0e0e0; }
.ms-proj-shot-ph { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; min-height:200px; border:1px dashed #e0e0e0; border-radius:8px; color:#8a929c; text-align:center; padding:22px; }
.ms-proj-shot-ph img { width:40px; height:40px; border-radius:8px; }
.ms-plugins { width:100%; border-collapse:collapse; }
.ms-plugins th { text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; color:#8a929c; font-weight:600; padding:8px 4px; border-bottom:1px solid #e0e0e0; }
.ms-plugins td { padding:10px 4px; font-size:14px; border-bottom:1px solid #f0f1f3; }
.ms-plugins tr:last-child td { border-bottom:0; }
.ms-plugins .ms-num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
@media (max-width:860px) { .ms-proj-grid { grid-template-columns:1fr; } }
</style>
<h1><?php echo htmlspecialchars($project->name); ?></h1>

<div class="ms-proj-meta">
    <?php echo MothershipHelper::statusDot(MothershipHelper::projectScanStatus($project), true); ?>
    <?php if ($type !== '') : ?><span class="sep">·</span> <?php echo htmlspecialchars(ucfirst($type)); ?><?php endif; ?>
    <?php if ($platform !== '') : ?><span class="sep">·</span> <?php echo htmlspecialchars($platform); ?><?php endif; ?>
    <?php if ($domain !== '') : ?><span class="sep">·</span> <?php echo $link($primaryUrl ?: 'https://' . $domain); ?><?php endif; ?>
</div>

<?php if (!empty($project->description)) : ?>
    <p style="max-width:820px;color:#3b4655;margin:0 0 18px;"><?php echo htmlspecialchars($project->description); ?></p>
<?php endif; ?>

<div class="ms-proj-grid">

    <!-- LEFT: Homepage, Domain, Hosting -->
    <div class="ms-proj-main">

        <div class="ms-psection">
            <div class="ms-psection__title">Homepage</div>
            <?php if ($screenshot !== '') : ?>
                <a href="<?php echo htmlspecialchars($primaryUrl ?: $screenshot); ?>" target="_blank" rel="noopener">
                    <img class="ms-proj-shot" src="<?php echo htmlspecialchars($screenshot); ?>" alt="Homepage screenshot of <?php echo htmlspecialchars($domain ?: $project->name); ?>">
                </a>
            <?php else : ?>
                <div class="ms-proj-shot-ph">
                    <?php if ($favicon !== '') : ?>
                        <img src="<?php echo htmlspecialchars($favicon); ?>" alt="">
                    <?php else : ?>
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18"/></svg>
                    <?php endif; ?>
                    <div>No homepage preview yet.<?php if ($primaryUrl !== '') : ?><br><a href="<?php echo htmlspecialchars($primaryUrl); ?>" target="_blank" rel="noopener">Visit <?php echo $prettyUrl($primaryUrl); ?> &#8599;</a><?php endif; ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="ms-psection">
            <div class="ms-psection__title">Domain</div>
            <?php if ($domainRow) : ?>
                <dl class="ms-proj-facts">
                    <dt>Domain</dt><dd><a href="<?php echo Route::_('index.php?option=com_mothership&view=domain&id=' . (int) $domainRow->id); ?>"><?php echo htmlspecialchars($domainRow->name); ?></a></dd>
                    <?php if (!empty($domainRow->registrar)) : ?><dt>Registrar</dt><dd><?php echo htmlspecialchars($domainRow->registrar); ?></dd><?php endif; ?>
                    <?php if (!empty($domainRow->dns_provider)) : ?><dt>DNS</dt><dd><?php echo htmlspecialchars($domainRow->dns_provider); ?></dd><?php endif; ?>
                    <dt>Registered</dt><dd><?php echo $fmtDate($domainRow->purchase_date); ?></dd>
                    <dt>Expires</dt><dd><?php echo $fmtDate($domainRow->expiration_date); ?></dd>
                    <dt>Status</dt><dd class="ms-status-cell"><?php echo MothershipHelper::statusDot($domainRow->status, true); ?></dd>
                </dl>
            <?php elseif ($domain !== '') : ?>
                <p style="color:#6b7480;margin:0;">Domain <strong><?php echo htmlspecialchars($domain); ?></strong> isn’t tracked in Mothership yet.</p>
            <?php else : ?>
                <p style="color:#6b7480;margin:0;">No domain recorded for this site.</p>
            <?php endif; ?>
        </div>

        <div class="ms-psection">
            <div class="ms-psection__title">Hosting</div>
            <?php if ($hosting) : ?>
                <dl class="ms-proj-facts">
                    <?php foreach ($hosting as $label => $value) : ?>
                        <dt><?php echo htmlspecialchars($label); ?></dt><dd><?php echo htmlspecialchars($value); ?></dd>
                    <?php endforeach; ?>
                </dl>
            <?php else : ?>
                <p style="color:#6b7480;margin:0;">No hosting information has been recorded for this site yet.</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- RIGHT: Site, Plugins -->
    <div class="ms-proj-side">

        <div class="ms-psection">
            <div class="ms-psection__title">Site</div>
            <dl class="ms-proj-facts">
                <?php if ($primaryUrl !== '') : ?><dt>Primary URL</dt><dd><?php echo $link($primaryUrl); ?></dd><?php endif; ?>
                <?php if ($domain !== '') : ?><dt>Domain</dt><dd><?php echo htmlspecialchars($domain); ?></dd><?php endif; ?>
                <dt>Platform</dt><dd><?php echo $platform !== '' ? htmlspecialchars($platform) : '—'; ?></dd>
                <?php if ($sitePill !== '') : ?><dt>Site status</dt><dd><?php echo $sitePill; ?></dd><?php endif; ?>
                <?php if (!empty($project->account_name)) : ?><dt>Account</dt><dd><?php echo htmlspecialchars($project->account_name); ?></dd><?php endif; ?>
                <dt>Created</dt><dd><?php echo $created; ?></dd>
                <?php if ($staging !== '') : ?><dt>Staging</dt><dd><?php echo $link($staging); ?></dd><?php endif; ?>
                <?php if ($dev !== '') : ?><dt>Dev</dt><dd><?php echo $link($dev); ?></dd><?php endif; ?>
            </dl>
            <div class="ms-proj-actions">
                <?php if ($adminUrl !== '') : ?>
                    <a class="btn btn-primary" href="<?php echo htmlspecialchars($adminUrl); ?>" target="_blank" rel="noopener">Open <?php echo $platform !== '' ? htmlspecialchars(strtok($platform, ' ')) : 'Site'; ?> Admin&nbsp;&#8599;</a>
                <?php endif; ?>
                <?php if ($primaryUrl !== '') : ?>
                    <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($primaryUrl); ?>" target="_blank" rel="noopener">Visit Site&nbsp;&#8599;</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="ms-psection">
            <div class="ms-psection__title">Plugins</div>
            <?php if ($pluginRows) : ?>
                <table class="ms-plugins">
                    <thead><tr><th>Plugin</th><th class="ms-num">Version</th></tr></thead>
                    <tbody>
                        <?php foreach ($pluginRows as $pl) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pl['name']); ?></td>
                                <td class="ms-num"><?php echo $pl['version'] !== '' ? htmlspecialchars($pl['version']) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p style="color:#6b7480;margin:0;">No plugin information has been recorded for this site yet.</p>
            <?php endif; ?>
        </div>

    </div>
</div>
