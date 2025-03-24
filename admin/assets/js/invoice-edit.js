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


/**
 * Mothership Invoice - Dynamic Account Dropdown Handler
 *
 * This script is used in the Joomla 5 admin interface of the Mothership component.
 * It controls the visibility and population of the "Account" dropdown based on the selected "Client".
 *
 * Behavior Overview:
 * ------------------
 * - On initial page load:
 *   - If no client is selected (value is ''), the Account field (.account_id_wrapper) is hidden.
 *   - The loading spinner (.account-loading-spinner) is also hidden.
 *
 * - When a client is selected:
 *   1. The Account field slides open over 200ms.
 *   2. A loading spinner fades in over 200ms, centered in the account container.
 *   3. An AJAX request is sent to:
 *      /administrator/index.php?option=com_mothership&task=invoice.getAccountsList&client_id={clientId}
 *   4. While waiting, the Account dropdown is hidden.
 *   5. On AJAX success:
 *      - The Account dropdown is cleared and populated with the returned list.
 *      - Each item is an object with { value, text, disable }.
 *      - The spinner fades out (200ms), and the dropdown fades in (200ms).
 *
 * - If the user selects a blank client:
 *   - The Account section fades out and slides closed over 200ms.
 *   - The spinner is reset and hidden.
 *
 * Expected JSON response format:
 * ------------------------------
 * [
 *   { "value": "", "text": "Please select an Account", "disable": false },
 *   { "value": 1,  "text": "Test Account",             "disable": false }
 * ]
 *
 * Security Considerations:
 * ------------------------
 * - CSRF protection should be implemented via Joomla.getOptions('csrf.token') and validated in PHP.
 * - Server-side must validate user permissions and input (client_id).
 * - The PHP controller should return a proper JsonResponse object.
 *
 * DOM Elements:
 * -------------
 * - #jform_client_id            : Client dropdown
 * - .account_id_wrapper         : Wrapper for the Account dropdown (shown/hidden)
 * - .account-loading-spinner    : Spinner shown during AJAX load
 * - #jform_account_id           : Account dropdown (populated dynamically)
 */

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
                // Clear and populate account dropdown
                accountSelect.empty();
    
                $.each(response, function (index, item) {
                    const option = $('<option>', {
                        value: item.value,
                        text: item.text,
                        disabled: item.disable === true
                    });
                    accountSelect.append(option);
                });
    
                // Fade out spinner, fade in dropdown
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