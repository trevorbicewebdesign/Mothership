<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$isAdmin = !empty($this->isAdmin);

$statusMeta = [
    'new'         => ['New', '#e8f0fe', '#1a56db'],
    'open'        => ['Open', '#e8f0fe', '#1a56db'],
    'in_progress' => ['In Progress', '#fff4e5', '#b45309'],
    'waiting'     => ['Waiting on Client', '#eef0f2', '#5a626c'],
    'resolved'    => ['Resolved', '#e6f4ea', '#137333'],
    'closed'      => ['Closed', '#eef0f2', '#5a626c'],
];
$prioMeta = [
    'low'    => ['Low', '#eef0f2', '#5a626c'],
    'normal' => ['Normal', '#e8f0fe', '#1a56db'],
    'high'   => ['High', '#fff4e5', '#b45309'],
    'urgent' => ['Urgent', '#fdecea', '#c81e1e'],
];
$pill = function ($meta, $key) {
    $m = $meta[$key] ?? [ucfirst(str_replace('_', ' ', (string) $key)), '#eef0f2', '#5a626c'];
    return '<span style="display:inline-block;padding:2px 10px;border-radius:100px;font-size:12px;font-weight:600;background:'
        . $m[1] . ';color:' . $m[2] . ';white-space:nowrap;">' . htmlspecialchars($m[0]) . '</span>';
};
?>
<div class="ms-page-head">
    <h1>Tickets</h1>
    <a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_mothership&view=tickets&layout=create'); ?>">+ New Ticket</a>
</div>
<table class="table" id="ticketsTable">
    <thead>
        <tr>
            <th>Subject</th>
            <?php if ($isAdmin) : ?><th>Client</th><?php endif; ?>
            <th>Account</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Updated</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($this->items)) : ?>
            <tr><td colspan="<?php echo $isAdmin ? 6 : 5; ?>">No tickets yet.</td></tr>
        <?php else : foreach ($this->items as $t) :
            $updated = $t->modified ?: $t->created;
            $url = Route::_('index.php?option=com_mothership&view=ticket&id=' . (int) $t->id); ?>
            <tr>
                <td>
                    <a href="<?php echo $url; ?>"><?php echo htmlspecialchars($t->subject); ?></a>
                    <?php if ($t->reply_count > 0) : ?><small style="color:#6b7480;"> · <?php echo (int) $t->reply_count; ?> repl<?php echo $t->reply_count == 1 ? 'y' : 'ies'; ?></small><?php endif; ?>
                </td>
                <?php if ($isAdmin) : ?><td><?php echo htmlspecialchars($t->client_name ?? ''); ?></td><?php endif; ?>
                <td><?php echo htmlspecialchars($t->account_name ?? '—'); ?></td>
                <td><?php echo $pill($statusMeta, $t->status); ?></td>
                <td><?php echo $pill($prioMeta, $t->priority); ?></td>
                <td data-order="<?php echo $updated; ?>"><?php echo $updated ? date('M j, Y', strtotime($updated)) : '—'; ?></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<?php if (!empty($this->items)) : $statusIdx = $isAdmin ? 3 : 2; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js"></script>
<script>
jQuery(function ($) {
    var el = document.getElementById('ticketsTable');
    if (el && $.fn.dataTable && !$.fn.dataTable.isDataTable(el)) {
        $(el).DataTable({
            responsive: true,
            order: [],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            columnDefs: [
                { targets: 0, responsivePriority: 1 },                       // Subject
                { targets: <?php echo $statusIdx; ?>, responsivePriority: 2 } // Status
            ],
            language: { search: '', searchPlaceholder: 'Search tickets…' }
        });
    }
});
</script>
<?php endif; ?>
