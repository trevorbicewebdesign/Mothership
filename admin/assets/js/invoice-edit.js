/**
 * This jQuery script manages dynamic invoice item calculations in a Joomla-based admin interface.
 * It ensures that values such as quantity, rate, subtotal, and total are automatically synchronized
 * and formatted as the user interacts with invoice line item fields.
 *
 * Core Features:
 * - Converts hours and minutes into decimal "quantity" format (e.g., 1h 30m → 1.50)
 * - Converts "quantity" back into hours and minutes when edited directly
 * - Dynamically calculates subtotal for each invoice item (rate × quantity)
 * - Aggregates subtotals to compute the total invoice value
 * - Formats currency values to two decimal places, but only on `blur` (after editing is complete)
 * - Avoids interfering with user input by not formatting values during typing
 *
 * Event Bindings:
 * - `input` on hours/minutes → converts to quantity and updates subtotal
 * - `input` on quantity → converts to hours/minutes and updates subtotal (but defers formatting)
 * - `blur` on quantity → formats the value to 2 decimal places
 * - `blur` on rate → formats the rate and updates subtotal
 *
 * Initialization:
 * - On document ready, all existing invoice item rows are normalized by:
 *    - Converting quantity to hours/minutes
 *    - Calculating and formatting subtotals
 *
 * Dependencies: jQuery (assumes Joomla admin template includes it)
 */

jQuery(document).ready(function ($) {

    function formatCurrency(value) {
        return parseFloat(value).toFixed(2);
    }

    function updateSubtotal(row) {
        const quantityInput = $(row).find('input[name$="[quantity]"]');
        const rateInput = $(row).find('input[name$="[rate]"]');

        let quantity = parseFloat(quantityInput.val()) || 0;
        let rate = parseFloat(rateInput.val()) || 0;

        // Format Rate
        rateInput.val(formatCurrency(rate));

        const subtotal = rate * quantity;
        $(row).find('input[name$="[subtotal]"]').val(formatCurrency(subtotal));

        updateInvoiceTotal();
    }

    function updateInvoiceTotal() {
        let invoiceTotal = 0;
        $('#invoice-items-table tbody tr').each(function () {
            const subtotal = parseFloat($(this).find('input[name$="[subtotal]"]').val()) || 0;
            invoiceTotal += subtotal;
        });

        $('#jform_total').val(formatCurrency(invoiceTotal));
    }

    function hoursMinutesToQuantity(row) {
        const hoursInput = $(row).find('input[name$="[hours]"]');
        const minutesInput = $(row).find('input[name$="[minutes]"]');

        let hours = parseInt(hoursInput.val()) || 0;
        let minutes = parseInt(minutesInput.val()) || 0;

        // Ensure hours/minutes are integers
        hoursInput.val(hours);
        minutesInput.val(minutes);

        const totalHours = hours + (minutes / 60);
        $(row).find('input[name$="[quantity]"]').val(formatCurrency(totalHours));
    }

    function quantityToHoursMinutes(row) {
        const quantity = parseFloat($(row).find('input[name$="[quantity]"]').val()) || 0;
        let hours = Math.floor(quantity);
        let minutes = Math.round((quantity - hours) * 60);

        // Adjust if minutes hit exactly 60
        if (minutes >= 60) {
            hours += 1;
            minutes -= 60;
        }

        $(row).find('input[name$="[hours]"]').val(hours);
        $(row).find('input[name$="[minutes]"]').val(minutes);
    }

    // Convert from hours/minutes to quantity on input
    $('#invoice-items-table tbody').on('input', 'input[name$="[hours]"], input[name$="[minutes]"]', function () {
        const row = $(this).closest('tr');
        hoursMinutesToQuantity(row);
        updateSubtotal(row);
    });

    // Convert from quantity to hours/minutes and update subtotal, but don't reformat mid-input
    $('#invoice-items-table tbody').on('input', 'input[name$="[quantity]"]', function () {
        const row = $(this).closest('tr');
        quantityToHoursMinutes(row);
        updateSubtotal(row);
    });

    // Format quantity only on blur to avoid disrupting typing
    $('#invoice-items-table tbody').on('blur', 'input[name$="[quantity]"]', function () {
        const val = parseFloat($(this).val()) || 0;
        $(this).val(formatCurrency(val));
    });

    // Format and update subtotal on blur of rate
    $('#invoice-items-table tbody').on('blur', 'input[name$="[rate]"]', function () {
        const row = $(this).closest('tr');
        const val = parseFloat($(this).val()) || 0;
        $(this).val(formatCurrency(val));
        updateSubtotal(row);
    });

    // Initialize subtotals and formatting on page load
    $('#invoice-items-table tbody tr').each(function () {
        quantityToHoursMinutes(this);
        updateSubtotal(this);
    });
});


jQuery(document).ready(function ($) {
    const clientSelect = $('#jform_client_id');
    const accountWrapper = $('.account_id_wrapper');
    const spinner = $('.account-loading-spinner');
    const accountSelect = $('#jform_account_id');

    function isNewInvoice() {
        return clientSelect.val() === '';
    }

    function revealAccountField(clientId) {
        if (accountWrapper.is(':visible')) return;

        // Initial state
        accountWrapper.css({
            display: 'block',
            overflow: 'hidden',
            height: 0,
            opacity: 0
        });
        spinner.css({
            display: 'block',
            opacity: 0
        });

        accountWrapper.css('opacity', 0);

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
                    // Fade in spinner
                    spinner.animate({ opacity: 1 }, {
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

    function hideAccountField() {
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

                    spinner.css({
                        display: 'none',
                        opacity: ''
                    });
                }
            }
        );
    }

    function loadAccountsForClient(clientId) {
        const ajaxUrl = '/administrator/index.php?option=com_mothership&task=invoice.getAccountsList&client_id=' + clientId;

        $.ajax({
            url: ajaxUrl,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                // Clear existing options
                accountSelect.empty();

                // Add default option
                accountSelect.append($('<option>', {
                    value: '',
                    text: 'Please select an Account'
                }));

                // Populate options
                $.each(response, function (index, item) {
                    accountSelect.append($('<option>', {
                        value: item.id,
                        text: item.name
                    }));
                });

                // Hide spinner, fade in dropdown
                spinner.animate({ opacity: 0 }, {
                    duration: 200,
                    easing: 'swing',
                    complete: function () {
                        spinner.css('display', 'none');

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

                spinner.fadeOut(200);
            }
        });
    }

    // On page load
    if (isNewInvoice()) {
        accountWrapper.hide();
        spinner.hide();
    }

    // On client change
    clientSelect.on('change', function () {
        const selectedVal = $(this).val();

        if (selectedVal === '') {
            hideAccountField();
        } else {
            revealAccountField(selectedVal);
        }
    });
});