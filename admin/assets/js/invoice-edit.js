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
