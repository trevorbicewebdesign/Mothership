<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$accounts = $this->accounts ?? [];
$projects = $this->projects ?? [];
?>
<h1>New Ticket</h1>
<form action="<?php echo Route::_('index.php?option=com_mothership&task=ticket.save'); ?>" method="post" enctype="multipart/form-data" style="max-width:680px;">

    <?php if (!empty($accounts)) : ?>
        <div class="mb-3">
            <label class="form-label" for="account_id">Account</label>
            <select class="form-select" id="account_id" name="account_id">
                <?php if (count($accounts) > 1) : ?>
                    <option value="">&mdash; Select an account &mdash;</option>
                <?php endif; ?>
                <?php foreach ($accounts as $a) : ?>
                    <option value="<?php echo (int) $a->id; ?>"><?php echo htmlspecialchars($a->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="mb-3" id="projectField" style="display:none;">
        <label class="form-label" for="project_id">Project <span style="color:#8a929c;font-weight:400;">(optional)</span></label>
        <select class="form-select" id="project_id" name="project_id">
            <option value="">&mdash; None &mdash;</option>
            <option value="new">+ New project&hellip;</option>
        </select>
    </div>
    <div class="mb-3" id="newProjectNameWrap" style="display:none;">
        <label class="form-label" for="new_project_name">New project name <span style="color:#8a929c;font-weight:400;">(optional)</span></label>
        <input class="form-control" type="text" id="new_project_name" name="new_project_name" placeholder="What would you like to call it?">
    </div>

    <div class="mb-3">
        <label class="form-label" for="subject">Subject</label>
        <input class="form-control" type="text" id="subject" name="subject" required placeholder="A brief summary of your request">
    </div>

    <div class="mb-3">
        <label class="form-label" for="priority">Priority</label>
        <select class="form-select" id="priority" name="priority" style="max-width:220px;">
            <option value="low">Low</option>
            <option value="normal" selected>Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label" for="description">Details</label>
        <textarea class="form-control" id="description" name="description" rows="7" placeholder="Tell us what you need — the more detail, the faster we can help."></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label" for="reference_url">Reference URL <span style="color:#8a929c;font-weight:400;">(optional)</span></label>
        <input class="form-control" type="url" id="reference_url" name="reference_url" placeholder="https://example.com/the-page-in-question">
    </div>

    <div class="mb-3">
        <label class="form-label">Screenshots <span style="color:#8a929c;font-weight:400;">(optional)</span></label>
        <div class="ms-dropzone" data-ms-dropzone>
            <input type="file" name="attachments[]" accept="image/png,image/jpeg,image/gif,image/webp" multiple hidden>
            <div class="ms-dropzone__prompt">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 13v6H5v-6H3v8h18v-8h-2zM11 4.83 8.41 7.41 7 6l5-5 5 5-1.41 1.41L13 4.83V16h-2V4.83z"/></svg>
                <div><strong>Drag &amp; drop screenshots</strong> or <span class="ms-dz-browse">browse</span></div>
                <div class="ms-dropzone__hint">PNG, JPG, GIF, WebP &middot; up to 8&nbsp;MB each</div>
            </div>
            <div class="ms-dropzone__previews"></div>
        </div>
    </div>

    <div style="display:flex;gap:14px;align-items:center;">
        <button type="submit" class="btn btn-primary">Submit Ticket</button>
        <a href="<?php echo Route::_('index.php?option=com_mothership&view=tickets'); ?>">Cancel</a>
    </div>

    <?php echo HTMLHelper::_('form.token'); ?>
</form>

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

    // Account → Project drill-down. The Project field stays hidden until an account
    // is selected, then its options are filtered to that account. "None" and
    // "+ New project" are always available. A single account is pre-selected, so the
    // Project field appears right away.
    var projectsData = <?php echo json_encode(array_values(array_map(function ($p) {
        return ['id' => (int) $p->id, 'name' => (string) $p->name, 'account_id' => (int) $p->account_id];
    }, $projects))); ?>;
    var accountSel   = document.getElementById('account_id');
    var projectField = document.getElementById('projectField');
    var projSel      = document.getElementById('project_id');
    var npWrap       = document.getElementById('newProjectNameWrap');

    function rebuildProjects(accountId) {
        projSel.innerHTML = '';
        projSel.add(new Option('— None —', ''));
        projectsData.filter(function (p) { return p.account_id === accountId; })
                    .forEach(function (p) { projSel.add(new Option(p.name, String(p.id))); });
        projSel.add(new Option('+ New project…', 'new'));
        if (npWrap) { npWrap.style.display = 'none'; }
    }

    function syncProjectField() {
        var val = accountSel ? accountSel.value : '';
        if (val) {
            rebuildProjects(parseInt(val, 10));
            projectField.style.display = '';
        } else {
            projectField.style.display = 'none';
            if (npWrap) { npWrap.style.display = 'none'; }
        }
    }

    if (accountSel) {
        accountSel.addEventListener('change', syncProjectField);
        syncProjectField(); // reveal immediately when a single account is pre-selected
    }

    if (projSel && npWrap) {
        projSel.addEventListener('change', function () {
            npWrap.style.display = this.value === 'new' ? '' : 'none';
        });
    }
})();
</script>
