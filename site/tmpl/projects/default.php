<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

$multi = MothershipHelper::userHasMultipleClients();

// Map "client_id|domain" -> domain record id (one query) so the Domain column can
// link to the domain page without a lookup per row.
$domainMap = [];
$clientIds = array_values(array_unique(array_map(fn ($p) => (int) $p->client_id, (array) $this->projects)));
if ($clientIds) {
    $db = Factory::getContainer()->get('DatabaseDriver');
    $db->setQuery(
        'SELECT id, name, client_id FROM ' . $db->quoteName('#__mothership_domains')
        . ' WHERE ' . $db->quoteName('client_id') . ' IN (' . implode(',', $clientIds) . ')'
    );
    foreach ((array) $db->loadObjectList() as $d) {
        $domainMap[$d->client_id . '|' . strtolower($d->name)] = (int) $d->id;
    }
}

// DataTables needs jQuery, which the site template may not load.
HTMLHelper::_('jquery.framework');

require __DIR__ . '/../_list_ui.php';
?>
<div class="msd-page">
    <div class="msd-head">
        <h1 class="msd-title">Projects</h1>
    </div>
    <p class="msd-sub">Websites and projects we manage for you.</p>

    <div class="msd-legend-inline">
        <span class="i"><span class="msd-dot" style="background:#16a34a;"></span> Online</span>
        <span class="i"><span class="msd-dot" style="background:#dc2626;"></span> Offline</span>
        <span class="i"><span class="msd-dot" style="background:#9aa0a6;"></span> Unknown</span>
    </div>

    <div class="msd-panel">
        <div class="msd-scroll">
        <table class="table projectsTable msd-table" id="projectsTable">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Domain</th>
                    <?php if ($multi) : ?><th>Client</th><?php endif; ?>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($this->projects)) : ?>
                    <tr>
                        <td colspan="<?php echo $multi ? 6 : 5; ?>">No projects found.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($this->projects as $projects) : ?>
                    <tr>
                        <?php $pStatus = MothershipHelper::projectScanStatus($projects); ?>
                        <td class="ms-status-cell" data-order="<?php echo htmlspecialchars($pStatus); ?>"><?php echo MothershipHelper::statusDot($pStatus, true); ?></td>
                        <td><?php echo MothershipHelper::projectTypeLabel($projects); ?></td>
                        <td>
                            <a href="<?php echo Route::_("index.php?option=com_mothership&view=project&id={$projects->id}"); ?>"><?php echo htmlspecialchars($projects->name); ?></a>
                            <?php if ($u = MothershipHelper::projectUrl($projects)) : ?>
                                <div class="ms-proj-url"><a href="<?php echo htmlspecialchars($u); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(preg_replace('#^https?://#i', '', rtrim($u, '/'))); ?></a></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $reg   = MothershipHelper::projectDomain($projects);
                            $domId = $reg ? ($domainMap[$projects->client_id . '|' . strtolower($reg)] ?? null) : null;
                            if ($domId) {
                                echo '<a href="' . Route::_('index.php?option=com_mothership&view=domain&id=' . $domId) . '">' . htmlspecialchars($reg) . '</a>';
                            } else {
                                echo $reg ? htmlspecialchars($reg) : '—';
                            }
                            ?>
                        </td>
                        <?php if ($multi) : ?><td><a href="<?php echo Route::_("index.php?option=com_mothership&view=client&id={$projects->client_id}"); ?>"><?php echo htmlspecialchars((string) $projects->client_name); ?></a></td><?php endif; ?>
                        <td class="num" data-order="<?php echo $projects->created; ?>"><?php echo $projects->created ? date('M j, Y', strtotime($projects->created)) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php if (!empty($this->projects)) : ?>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js"></script>
<script>
jQuery(function ($) {
    var el = document.getElementById('projectsTable');
    if (el && $.fn.dataTable && !$.fn.dataTable.isDataTable(el)) {
        $(el).DataTable({
            responsive: true,
            order: [],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            columnDefs: [
                { targets: 2, responsivePriority: 1 }, // Name
                { targets: 0, responsivePriority: 2 }, // Status
                { targets: 3, responsivePriority: 3 }, // Domain
                { targets: 1, responsivePriority: 4 }  // Type
            ],
            language: { search: '', searchPlaceholder: 'Search projects…' }
        });
    }
});
</script>
<?php endif; ?>
