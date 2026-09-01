<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$t       = $this->item;
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
$statusLabels = ['new' => 'New', 'open' => 'Open', 'in_progress' => 'In Progress', 'waiting' => 'Waiting on Client', 'resolved' => 'Resolved', 'closed' => 'Closed'];
?>
<div class="ms-page-head">
    <h1>#<?php echo (int) $t->id; ?> · <?php echo htmlspecialchars($t->subject); ?></h1>
    <a href="<?php echo Route::_('index.php?option=com_mothership&view=tickets'); ?>">← Back to Tickets</a>
</div>
<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:10px 0 18px;">
    <?php echo $pill($statusMeta, $t->status); ?>
    <?php echo $pill($prioMeta, $t->priority); ?>
    <?php if (!empty($t->account_name)) : ?><span style="color:#6b7480;font-size:13px;">Account: <?php echo htmlspecialchars($t->account_name); ?></span><?php endif; ?>
    <span style="color:#6b7480;font-size:13px;">Opened <?php echo date('M j, Y', strtotime($t->created)); ?></span>
    <?php if (!empty($t->reference_url)) : $refOk = (bool) preg_match('#^https?://#i', $t->reference_url); ?>
        <span style="color:#6b7480;font-size:13px;">Reference:
            <?php if ($refOk) : ?><a href="<?php echo htmlspecialchars($t->reference_url); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($t->reference_url); ?></a><?php else : echo htmlspecialchars($t->reference_url); endif; ?>
        </span>
    <?php endif; ?>
</div>

<div class="ms-ticket-body">

<div style="background:#fff;border:1px solid var(--ms-border,#e0e0e0);border-radius:8px;padding:14px 16px;margin-bottom:18px;">
    <?php echo nl2br(htmlspecialchars($t->description ?? '')); ?>
</div>

<?php if (!empty($this->attachments)) : ?>
<div style="margin-bottom:18px;">
    <div style="font-size:12.5px;color:#6b7480;margin-bottom:8px;">Screenshots</div>
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        <?php foreach ($this->attachments as $att) : $src = Route::_('index.php?option=com_mothership&task=ticket.attachment&id=' . (int) $att->id); ?>
            <a href="<?php echo $src; ?>" target="_blank" rel="noopener" title="<?php echo htmlspecialchars($att->original_name ?? ''); ?>">
                <img src="<?php echo $src; ?>" alt="<?php echo htmlspecialchars($att->original_name ?? 'attachment'); ?>" style="width:112px;height:84px;object-fit:cover;border:1px solid var(--ms-border,#e0e0e0);border-radius:6px;background:#fff;display:block;">
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php foreach ($this->replies as $r) :
    if ($r->is_internal && !$isAdmin) {
        continue; // internal notes are hidden from clients
    } ?>
    <div style="border:1px solid <?php echo $r->is_internal ? '#f0d9c0' : 'var(--ms-border,#e0e0e0)'; ?>;background:<?php echo $r->is_internal ? '#fcf7f0' : '#fff'; ?>;border-radius:8px;padding:12px 16px;margin-bottom:12px;">
        <div style="font-size:12.5px;color:#6b7480;margin-bottom:6px;">
            <strong style="color:var(--ms-text,#1d2327);"><?php echo htmlspecialchars($r->author_name ?? 'User'); ?></strong>
            · <?php echo date('M j, Y g:i a', strtotime($r->created)); ?>
            <?php if ($r->is_internal) : ?> · <em>internal note</em><?php endif; ?>
        </div>
        <div><?php echo nl2br(htmlspecialchars($r->body ?? '')); ?></div>
    </div>
<?php endforeach; ?>

<?php if ($t->status !== 'closed' || $isAdmin) : ?>
<hr/>
<h4>Add a reply</h4>
<form action="<?php echo Route::_('index.php?option=com_mothership&task=ticket.reply'); ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="ticket_id" value="<?php echo (int) $t->id; ?>">
    <div class="mb-3">
        <textarea class="form-control" name="body" rows="5" placeholder="Write a reply…"></textarea>
    </div>
    <div class="mb-3">
        <div class="ms-dropzone" data-ms-dropzone>
            <input type="file" name="attachments[]" accept="image/png,image/jpeg,image/gif,image/webp" multiple hidden>
            <div class="ms-dropzone__prompt">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 13v6H5v-6H3v8h18v-8h-2zM11 4.83 8.41 7.41 7 6l5-5 5 5-1.41 1.41L13 4.83V16h-2V4.83z"/></svg>
                <div><strong>Drag &amp; drop screenshots</strong> or <span class="ms-dz-browse">browse</span></div>
            </div>
            <div class="ms-dropzone__previews"></div>
        </div>
    </div>
    <?php if ($isAdmin) : ?>
    <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
        <label class="form-label" style="margin:0;">Status
            <select name="status" class="form-select" style="display:inline-block;width:auto;margin-left:6px;">
                <?php foreach ($statusLabels as $k => $lbl) : ?>
                    <option value="<?php echo $k; ?>" <?php echo $t->status === $k ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="display:flex;align-items:center;gap:6px;margin:0;">
            <input type="checkbox" name="is_internal" value="1"> Internal note (not shown to the client)
        </label>
    </div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary">Send Reply</button>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
<?php else : ?>
    <p style="color:#6b7480;">This ticket is closed.</p>
<?php endif; ?>

</div><!-- /.ms-ticket-body -->

<script>
(function () {
    function human(n){ return n < 1048576 ? Math.round(n/1024)+' KB' : (n/1048576).toFixed(1)+' MB'; }
    document.querySelectorAll('[data-ms-dropzone]').forEach(function (dz) {
        if (dz.dataset.msInit) return; dz.dataset.msInit = '1';
        var input = dz.querySelector('input[type=file]');
        var prev  = dz.querySelector('.ms-dropzone__previews');
        var files = [];
        function sync(){ var dt = new DataTransfer(); files.forEach(function(f){ dt.items.add(f); }); input.files = dt.files; render(); }
        function render(){
            prev.innerHTML = '';
            files.forEach(function(f, i){
                var url = URL.createObjectURL(f);
                var el = document.createElement('div'); el.className = 'ms-dz-item';
                el.innerHTML = '<img src="'+url+'" alt=""><button type="button" class="ms-dz-remove" aria-label="Remove">&times;</button><span class="ms-dz-name"></span>';
                el.querySelector('.ms-dz-name').textContent = f.name + ' · ' + human(f.size);
                el.querySelector('.ms-dz-remove').addEventListener('click', function(e){ e.stopPropagation(); files.splice(i,1); sync(); });
                prev.appendChild(el);
            });
        }
        function add(list){ Array.prototype.forEach.call(list, function(f){ if (f.type.indexOf('image/') === 0) files.push(f); }); sync(); }
        dz.addEventListener('click', function(e){ if (e.target.closest('.ms-dz-remove') || e.target.closest('.ms-dropzone__previews')) return; input.click(); });
        input.addEventListener('change', function(){ add(input.files); });
        ['dragenter','dragover'].forEach(function(ev){ dz.addEventListener(ev, function(e){ e.preventDefault(); dz.classList.add('is-drag'); }); });
        dz.addEventListener('dragleave', function(e){ if (!dz.contains(e.relatedTarget)) dz.classList.remove('is-drag'); });
        dz.addEventListener('drop', function(e){ e.preventDefault(); dz.classList.remove('is-drag'); if (e.dataTransfer && e.dataTransfer.files) add(e.dataTransfer.files); });
    });
})();
</script>
