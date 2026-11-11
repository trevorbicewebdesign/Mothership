/**
 * Mothership: Expense edit cascade.
 *
 * Client → Account → Project are all optional (an expense may be unassigned).
 * Changing the client repopulates the account dropdown; changing the account
 * repopulates the project dropdown. Both keep a leading blank option so the
 * expense can be left assigned to the client (or account) alone, or to nothing.
 *
 * Endpoints:
 *   task=expense.getAccountsList&client_id={id}   → [{value,text,disable}]
 *   task=expense.getProjectsList&account_id={id}  → [{value,text,disable}]
 */
jQuery(document).ready(function ($) {
    const clientSelect  = $('#jform_client_id');
    const accountSelect = $('#jform_account_id');
    const projectSelect = $('#jform_project_id');
    const accountSpinner = $('.account-loading-spinner');
    const projectSpinner = $('.project-loading-spinner');

    const base = '/administrator/index.php?option=com_mothership';

    function repopulate(select, spinner, items, keepValue) {
        select.empty();
        $.each(items, function (i, item) {
            select.append($('<option>', {
                value: item.value,
                text: item.text,
                disabled: item.disable === true
            }));
        });
        // Restore the previous selection if it is still present.
        if (keepValue && select.find('option[value="' + keepValue + '"]').length) {
            select.val(keepValue);
        }
        spinner.hide();
        select.closest('.control-group, .row').show();
        // Re-sync Chosen if it enhanced the select.
        if (select.data && select.trigger) {
            select.trigger('liszt:updated').trigger('chosen:updated');
        }
    }

    function loadAccounts(clientId, keepValue, thenLoadProjects) {
        if (!clientId) {
            repopulate(accountSelect, accountSpinner, [{ value: '', text: accountSelect.find('option').first().text() }], '');
            repopulate(projectSelect, projectSpinner, [{ value: '', text: projectSelect.find('option').first().text() }], '');
            return;
        }
        accountSpinner.show();
        $.ajax({
            url: base + '&task=expense.getAccountsList&client_id=' + encodeURIComponent(clientId),
            method: 'GET',
            dataType: 'json'
        }).done(function (data) {
            repopulate(accountSelect, accountSpinner, data || [], keepValue);
            if (thenLoadProjects) {
                loadProjects(accountSelect.val(), projectSelect.data('current') || '');
            }
        }).fail(function () {
            accountSpinner.hide();
            console.error('Expense: failed to load accounts for client ' + clientId);
        });
    }

    function loadProjects(accountId, keepValue) {
        if (!accountId) {
            repopulate(projectSelect, projectSpinner, [{ value: '', text: projectSelect.find('option').first().text() }], '');
            return;
        }
        projectSpinner.show();
        $.ajax({
            url: base + '&task=expense.getProjectsList&account_id=' + encodeURIComponent(accountId),
            method: 'GET',
            dataType: 'json'
        }).done(function (data) {
            repopulate(projectSelect, projectSpinner, data || [], keepValue);
        }).fail(function () {
            projectSpinner.hide();
            console.error('Expense: failed to load projects for account ' + accountId);
        });
    }

    // Remember the currently-saved project so a fresh account fetch can re-select it.
    projectSelect.data('current', projectSelect.val());

    clientSelect.on('change', function () {
        loadAccounts($(this).val(), '', true);
    });

    accountSelect.on('change', function () {
        projectSelect.data('current', '');
        loadProjects($(this).val(), '');
    });
});
