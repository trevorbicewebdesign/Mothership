/**
 * Mothership Ticket — Client → Account → Project drill-down.
 *
 * Mirrors the cascade used on the Invoice/Domain edit screens:
 *  - Selecting a Client reveals and populates the Account dropdown.
 *  - Selecting an Account reveals and populates the Project dropdown.
 *  - Clearing a Client hides Account (and Project); clearing an Account hides Project.
 *
 * AJAX endpoints (JSON list of {value, text, disable}):
 *  - task=ticket.getAccountsList&client_id={id}
 *  - task=ticket.getProjectsList&account_id={id}
 *
 * DOM contract (set up in tmpl/ticket/edit.php):
 *  - #jform_client_id, #jform_account_id, #jform_project_id
 *  - .account_id_wrapper / .account-loading-spinner
 *  - .project_id_wrapper / .project-loading-spinner
 */
jQuery(document).ready(function ($) {
    const clientSelect   = $('#jform_client_id');
    const accountSelect  = $('#jform_account_id');
    const projectSelect  = $('#jform_project_id');
    const accountWrapper = $('.account_id_wrapper');
    const projectWrapper = $('.project_id_wrapper');
    const accountSpinner = $('.account-loading-spinner');
    const projectSpinner = $('.project-loading-spinner');

    function slideOpen(wrapper, spinner, done) {
        wrapper.css({ display: 'block', overflow: 'hidden', height: 0, opacity: 0 });
        spinner.css({ display: 'block', opacity: 0 });

        const clone = wrapper.clone().css({
            visibility: 'hidden', height: 'auto', display: 'block', position: 'absolute', left: -9999
        }).appendTo('body');
        const targetHeight = clone.outerHeight();
        clone.remove();

        wrapper.animate({ height: targetHeight }, {
            duration: 200, easing: 'swing',
            complete: function () {
                spinner.animate({ opacity: 1 }, { duration: 200, easing: 'swing', complete: done });
            }
        });
    }

    function slideClosed(wrapper, spinner) {
        const currentHeight = wrapper.outerHeight();
        wrapper.css({ overflow: 'hidden', height: currentHeight, opacity: 1 });
        wrapper.animate({ height: 0, opacity: 0 }, {
            duration: 200, easing: 'swing',
            complete: function () {
                wrapper.css({ display: 'none', height: '', overflow: '', opacity: '' });
                spinner.css({ display: 'none', opacity: '' });
            }
        });
    }

    function populate(select, wrapper, spinner, response) {
        select.empty();
        $.each(response, function (i, item) {
            select.append($('<option>', {
                value: item.value,
                text: item.text,
                disabled: item.disable === true
            }));
        });
        spinner.animate({ opacity: 0 }, {
            duration: 200, easing: 'swing',
            complete: function () {
                spinner.css('display', 'none');
                wrapper.animate({ opacity: 1 }, {
                    duration: 200, easing: 'swing',
                    complete: function () {
                        wrapper.css({ height: '', overflow: '', opacity: '' });
                    }
                });
            }
        });
    }

    function loadAccounts(clientId) {
        $.ajax({
            url: '/administrator/index.php?option=com_mothership&task=ticket.getAccountsList&client_id=' + clientId,
            method: 'GET', dataType: 'json',
            success: function (response) { populate(accountSelect, accountWrapper, accountSpinner, response); },
            error: function () { console.error('Failed to load accounts for client_id=' + clientId); accountSpinner.fadeOut(200); }
        });
    }

    function loadProjects(accountId) {
        $.ajax({
            url: '/administrator/index.php?option=com_mothership&task=ticket.getProjectsList&account_id=' + accountId,
            method: 'GET', dataType: 'json',
            success: function (response) { populate(projectSelect, projectWrapper, projectSpinner, response); },
            error: function () { console.error('Failed to load projects for account_id=' + accountId); projectSpinner.fadeOut(200); }
        });
    }

    // Initial state: hide whichever levels have no parent selected.
    if (clientSelect.val() === '') {
        accountWrapper.hide();
        accountSpinner.hide();
        projectWrapper.hide();
        projectSpinner.hide();
    } else if (accountSelect.val() === '') {
        projectWrapper.hide();
        projectSpinner.hide();
    }

    clientSelect.on('change', function () {
        slideClosed(projectWrapper, projectSpinner);
        const val = $(this).val();
        if (val) {
            slideOpen(accountWrapper, accountSpinner, function () { loadAccounts(val); });
        } else {
            slideClosed(accountWrapper, accountSpinner);
        }
    });

    accountSelect.on('change', function () {
        const val = $(this).val();
        if (val) {
            projectWrapper.stop(true, true);
            slideOpen(projectWrapper, projectSpinner, function () { loadProjects(val); });
        } else {
            slideClosed(projectWrapper, projectSpinner);
        }
    });
});
