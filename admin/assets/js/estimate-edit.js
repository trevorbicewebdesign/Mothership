jQuery(document).ready(function ($) {

    /* ------------------------------------------------------------------
     *  Estimate line items: time_low / time  <->  quantity_low / quantity
     * ------------------------------------------------------------------ */

    function formatCurrency(value) {
        const n = parseFloat(value);
        if (isNaN(n)) {
            return '0.00';
        }
        return n.toFixed(2);
    }

    // Accepts "HH:MM" or plain numeric input
    function parseTimeToHours(value) {
        if (value === null || value === undefined) {
            return 0;
        }

        const str = String(value).trim();
        if (!str) {
            return 0;
        }

        // Match HH:MM
        const match = str.match(/^(\d{1,3}):(\d{2})$/);
        if (match) {
            const hours   = parseInt(match[1], 10) || 0;
            const minutes = parseInt(match[2], 10) || 0;
            return hours + (minutes / 60);
        }

        // Fallback: treat as numeric hours
        const n = parseFloat(str);
        return isNaN(n) ? 0 : n;
    }

    // Convert decimal hours → "HH:MM"
    function hoursToTimeString(hours) {
        const n = parseFloat(hours);
        if (isNaN(n) || n <= 0) {
            return '';
        }

        const totalMinutes = Math.round(n * 60);
        const h = Math.floor(totalMinutes / 60);
        const m = totalMinutes % 60;

        return h + ':' + String(m).padStart(2, '0');
    }

    function toNumber(val, fallback = 0) {
        const n = parseFloat(val);
        return isNaN(n) ? fallback : n;
    }

    function updateEstimateTotals() {
        let lowTotalSum  = 0;
        let highTotalSum = 0;

        $('#estimate-items-table tbody tr').each(function () {
            const $row = $(this);

            const $lowField = $row.find(
                'input[name$="[low_total]"], input[name$="[subtotal_low]"]'
            );
            const $highField = $row.find(
                'input[name$="[high_total]"], input[name$="[subtotal]"]'
            );

            const low  = toNumber($lowField.val(), 0);
            const high = toNumber($highField.val(), 0);

            lowTotalSum  += low;
            highTotalSum += high;
        });

        const $totalLow  = $('#jform_total_low');
        const $totalHigh = $('#jform_total_high');

        if ($totalLow.length) {
            $totalLow.val(formatCurrency(lowTotalSum));
        }

        if ($totalHigh.length) {
            $totalHigh.val(formatCurrency(highTotalSum));
        }
    }

    // Totals are purely rate * quantity_(low/high)
    function recalcRowTotals(row) {
        const $row = $(row);

        const $qtyLowInput  = $row.find('input[name$="[quantity_low]"]');
        const $qtyHighInput = $row.find('input[name$="[quantity]"]');
        const $rateInput    = $row.find('input[name$="[rate]"]');

        const qtyLow  = $qtyLowInput.length  ? toNumber($qtyLowInput.val(), 0)  : 0;
        const qtyHigh = $qtyHighInput.length ? toNumber($qtyHighInput.val(), 0) : 0;
        const rate    = $rateInput.length    ? toNumber($rateInput.val(), 0)    : 0;

        const lowTotal  = rate * qtyLow;
        const highTotal = rate * qtyHigh;

        const $lowTotalField = $row.find(
            'input[name$="[low_total]"], input[name$="[subtotal_low]"]'
        );
        const $highTotalField = $row.find(
            'input[name$="[high_total]"], input[name$="[subtotal]"]'
        );

        if ($lowTotalField.length) {
            $lowTotalField.val(formatCurrency(lowTotal));
        }
        if ($highTotalField.length) {
            $highTotalField.val(formatCurrency(highTotal));
        }

        updateEstimateTotals();
    }

    // Sync helpers: keep time <-> quantity in lockstep

    function syncFromTimeLow(row) {
        const $row          = $(row);
        const $timeLowInput = $row.find('input[name$="[time_low]"]');
        const $qtyLowInput  = $row.find('input[name$="[quantity_low]"]');

        if (!$timeLowInput.length || !$qtyLowInput.length) {
            return;
        }

        const hours = parseTimeToHours($timeLowInput.val());
        if (hours > 0) {
            $qtyLowInput.val(formatCurrency(hours));
        } else if ($timeLowInput.val().trim() === '') {
            $qtyLowInput.val('');
        }

        recalcRowTotals(row);
    }

    function syncFromQuantityLow(row) {
        const $row          = $(row);
        const $qtyLowInput  = $row.find('input[name$="[quantity_low]"]');
        const $timeLowInput = $row.find('input[name$="[time_low]"]');

        if (!$qtyLowInput.length || !$timeLowInput.length) {
            return;
        }

        const qty = toNumber($qtyLowInput.val(), 0);
        if (qty > 0) {
            $timeLowInput.val(hoursToTimeString(qty));
        } else if ($qtyLowInput.val().trim() === '') {
            $timeLowInput.val('');
        }

        recalcRowTotals(row);
    }

    function syncFromTimeHigh(row) {
        const $row        = $(row);
        const $timeInput  = $row.find('input[name$="[time]"]');
        const $qtyInput   = $row.find('input[name$="[quantity]"]');

        if (!$timeInput.length || !$qtyInput.length) {
            return;
        }

        const hours = parseTimeToHours($timeInput.val());
        if (hours > 0) {
            $qtyInput.val(formatCurrency(hours));
        } else if ($timeInput.val().trim() === '') {
            $qtyInput.val('');
        }

        recalcRowTotals(row);
    }

    function syncFromQuantityHigh(row) {
        const $row       = $(row);
        const $qtyInput  = $row.find('input[name$="[quantity]"]');
        const $timeInput = $row.find('input[name$="[time]"]');

        if (!$qtyInput.length || !$timeInput.length) {
            return;
        }

        const qty = toNumber($qtyInput.val(), 0);
        if (qty > 0) {
            $timeInput.val(hoursToTimeString(qty));
        } else if ($qtyInput.val().trim() === '') {
            $timeInput.val('');
        }

        recalcRowTotals(row);
    }

    // Only wire up if this is actually the estimate edit screen
    if ($('#estimate-items-table').length) {

        const $tbody = $('#estimate-items-table tbody');

        // TIME LOW → QUANTITY_LOW
        $tbody.on('input change', 'input[name$="[time_low]"]', function () {
            const $row = $(this).closest('tr');
            syncFromTimeLow($row);
        });

        // QUANTITY_LOW → TIME LOW
        $tbody.on('input change', 'input[name$="[quantity_low]"]', function () {
            const $row = $(this).closest('tr');
            syncFromQuantityLow($row);
        });

        // TIME (HIGH) → QUANTITY (HIGH)
        $tbody.on('input change', 'input[name$="[time]"]', function () {
            const $row = $(this).closest('tr');
            syncFromTimeHigh($row);
        });

        // QUANTITY (HIGH) → TIME (HIGH)
        $tbody.on('input change', 'input[name$="[quantity]"]', function () {
            const $row = $(this).closest('tr');
            syncFromQuantityHigh($row);
        });

        // RATE changes just recalc totals using existing quantities
        $tbody.on('input change', 'input[name$="[rate]"]', function () {
            const $row = $(this).closest('tr');
            recalcRowTotals($row);
        });

        // Format number-ish fields only on blur (don’t disrupt typing)
        $tbody.on('blur', 'input[name$="[quantity]"], input[name$="[quantity_low]"]', function () {
            const val = parseFloat($(this).val());
            $(this).val(isNaN(val) ? '' : formatCurrency(val));
        });

        $tbody.on('blur', 'input[name$="[rate]"]', function () {
            const val = parseFloat($(this).val());
            $(this).val(isNaN(val) ? '' : formatCurrency(val));

            const $row = $(this).closest('tr');
            recalcRowTotals($row);
        });

        // Initialize existing rows on page load:
        // - prefer existing quantity, back-fill time if needed
        // - or, if quantity empty but time present, back-fill quantity
        $tbody.find('tr').each(function () {
            const $row          = $(this);
            const hasQtyLow     = $row.find('input[name$="[quantity_low]"]').val().trim() !== '';
            const hasTimeLow    = $row.find('input[name$="[time_low]"]').val().trim() !== '';
            const hasQtyHigh    = $row.find('input[name$="[quantity]"]').val().trim() !== '';
            const hasTimeHigh   = $row.find('input[name$="[time]"]').val().trim() !== '';

            if (hasQtyLow && !hasTimeLow) {
                syncFromQuantityLow($row);
            } else if (!hasQtyLow && hasTimeLow) {
                syncFromTimeLow($row);
            }

            if (hasQtyHigh && !hasTimeHigh) {
                syncFromQuantityHigh($row);
            } else if (!hasQtyHigh && hasTimeHigh) {
                syncFromTimeHigh($row);
            }

            recalcRowTotals($row);
        });
    }

    /* ------------------------------------------------------------------
     *  Client → Account → Project dynamic dropdowns (shared with estimate)
     * ------------------------------------------------------------------ */

    const clientSelect   = $('#jform_client_id');
    const accountWrapper = $('.account_id_wrapper');
    const projectWrapper = $('.project_id_wrapper');
    const accountSpinner = $('.account-loading-spinner');
    const projectSpinner = $('.project-loading-spinner');
    const accountSelect  = $('#jform_account_id');
    const projectSelect  = $('#jform_project_id');

    function isNewEstimate() {
        return clientSelect.length && clientSelect.val() === '';
    }

    function revealAccountField(clientId) {
        accountWrapper.css({
            display: 'block',
            overflow: 'hidden',
            height: 0,
            opacity: 0
        });
        accountSpinner.css({
            display: 'block',
            opacity: 0
        });

        const clone = accountWrapper.clone().css({
            visibility: 'hidden',
            height: 'auto',
            display: 'block',
            position: 'absolute',
            left: -9999
        }).appendTo('body');

        const targetHeight = clone.outerHeight();
        clone.remove();

        accountWrapper.animate(
            { height: targetHeight },
            {
                duration: 200,
                easing: 'swing',
                complete: function () {
                    accountSpinner.animate({ opacity: 1 }, {
                        duration: 200,
                        easing: 'swing',
                        complete: function () {
                            loadAccountsForClient(clientId);
                        }
                    });
                }
            }
        );
    }

    function revealProjectField(accountId) {
        if (!projectWrapper.length || projectWrapper.is(':visible')) {
            return;
        }

        projectWrapper.css({
            display: 'block',
            overflow: 'hidden',
            height: 0,
            opacity: 0
        });

        const clone = projectWrapper.clone().css({
            visibility: 'hidden',
            height: 'auto',
            display: 'block',
            position: 'absolute',
            left: -9999
        }).appendTo('body');

        const targetHeight = clone.outerHeight();
        clone.remove();

        projectWrapper.animate(
            { height: targetHeight },
            {
                duration: 200,
                easing: 'swing',
                complete: function () {
                    projectSpinner.animate({ opacity: 1 }, {
                        duration: 200,
                        easing: 'swing',
                        complete: function () {
                            loadProjectsForAccount(accountId);
                        }
                    });
                }
            }
        );
    }

    function hideAccountField() {
        if (!accountWrapper.length || !accountWrapper.is(':visible')) {
            return;
        }

        const currentHeight = accountWrapper.outerHeight();

        accountWrapper.css({
            overflow: 'hidden',
            height: currentHeight,
            opacity: 1
        });

        accountWrapper.animate(
            { height: 0, opacity: 0 },
            {
                duration: 200,
                easing: 'swing',
                complete: function () {
                    accountWrapper.css({
                        display: 'none',
                        height: '',
                        overflow: '',
                        opacity: ''
                    });

                    accountSpinner.css({
                        display: 'none',
                        opacity: ''
                    });
                }
            }
        );
    }

    function hideProjectsField() {
        if (!projectWrapper.length || !projectWrapper.is(':visible')) {
            return;
        }

        const currentHeight = projectWrapper.outerHeight();

        projectWrapper.css({
            overflow: 'hidden',
            height: currentHeight,
            opacity: 1
        });

        projectWrapper.animate(
            { height: 0, opacity: 0 },
            {
                duration: 200,
                easing: 'swing',
                complete: function () {
                    projectWrapper.css({
                        display: 'none',
                        height: '',
                        overflow: '',
                        opacity: ''
                    });

                    projectSpinner.css({
                        display: 'none',
                        opacity: ''
                    });
                }
            }
        );
    }

    function loadAccountsForClient(clientId) {
        const ajaxUrl = '/administrator/index.php' +
            '?option=com_mothership' +
            '&task=estimate.getAccountsList' +
            '&client_id=' + encodeURIComponent(clientId);

        $.ajax({
            url: ajaxUrl,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                accountSelect.empty();

                $.each(response, function (index, item) {
                    const option = $('<option>', {
                        value: item.value,
                        text: item.text,
                        disabled: item.disable === true
                    });
                    accountSelect.append(option);
                });

                accountSpinner.animate({ opacity: 0 }, {
                    duration: 200,
                    easing: 'swing',
                    complete: function () {
                        accountSpinner.css('display', 'none');

                        accountWrapper.animate({ opacity: 1 }, {
                            duration: 200,
                            easing: 'swing',
                            complete: function () {
                                accountWrapper.css({
                                    height: '',
                                    overflow: '',
                                    opacity: ''
                                });
                            }
                        });
                    }
                });
            },
            error: function () {
                console.error('Failed to fetch accounts for client_id=' + clientId);
                alert('Error loading accounts. Please try again.');
                accountSpinner.fadeOut(200);
            }
        });
    }

    function loadProjectsForAccount(accountId) {
        const ajaxUrl = '/administrator/index.php' +
            '?option=com_mothership' +
            '&task=estimate.getProjectsList' +
            '&account_id=' + encodeURIComponent(accountId);

        $.ajax({
            url: ajaxUrl,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                projectSelect.empty();

                $.each(response, function (index, item) {
                    const option = $('<option>', {
                        value: item.value,
                        text: item.text,
                        disabled: item.disable === true
                    });
                    projectSelect.append(option);
                });

                projectSpinner.animate({ opacity: 0 }, {
                    duration: 200,
                    easing: 'swing',
                    complete: function () {
                        projectSpinner.css('display', 'none');

                        projectWrapper.animate({ opacity: 1 }, {
                            duration: 200,
                            easing: 'swing',
                            complete: function () {
                                projectWrapper.css({
                                    height: '',
                                    overflow: '',
                                    opacity: ''
                                });
                            }
                        });
                    }
                });
            },
            error: function () {
                console.error('Failed to fetch projects for account_id=' + accountId);
                alert('Error loading projects. Please try again.');
                projectSpinner.fadeOut(200);
            }
        });
    }

    if (clientSelect.length) {
        if (isNewEstimate()) {
            accountWrapper.hide();
            accountSpinner.hide();
            projectWrapper.hide();
            projectSpinner.hide();
        }

        clientSelect.on('change', function () {
            hideProjectsField();

            const selectedVal = $(this).val();

            if (selectedVal) {
                revealAccountField(selectedVal);
            } else {
                hideAccountField();
            }
        });

        accountSelect.on('change', function () {
            const selectedVal = $(this).val();

            if (selectedVal) {
                revealProjectField(selectedVal);
            } else {
                hideProjectsField();
            }
        });
    }

    /* ------------------------------------------------------------------
     *  Default rate from client → estimate + line items
     * ------------------------------------------------------------------ */

    const $clientField = $('#jform_client_id');
    const $rateField   = $('#jform_rate');
    let userModifiedRate = false;

    if ($clientField.length && $rateField.length) {

        $rateField.on('input', function () {
            userModifiedRate = true;
        });

        $clientField.on('change', function () {
            const clientId = $(this).val();

            if (!clientId || userModifiedRate) {
                return;
            }

            $.ajax({
                url: 'index.php?option=com_mothership&task=client.getDefaultRate&id=' +
                    encodeURIComponent(clientId) + '&format=json',
                dataType: 'json',
                success: function (data) {
                    if (typeof data.default_rate !== 'undefined') {
                        $rateField.val(data.default_rate);

                        $('#estimate-items-table tbody tr').each(function () {
                            const $row      = $(this);
                            const rateInput = $row.find('input[name$="[rate]"]');
                            rateInput.val(data.default_rate);
                            recalcRowTotals($row);
                        });
                    }
                }
            });
        });
    }
});
