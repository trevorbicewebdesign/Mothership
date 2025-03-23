/*
Invoice Row Calculation and Update Logic:

When a user changes any of these fields in an invoice item row, the following occurs:

- Hours field:
  - Automatically updates the "Quantity" field based on the combined hours and minutes.
  - Recalculates the subtotal for the row.

- Minutes field:
  - Automatically updates the "Quantity" field based on the combined hours and minutes.
  - Recalculates the subtotal for the row.

- Quantity field:
  - Automatically updates the "Hours" and "Minutes" fields based on the decimal value entered in the quantity.
  - Recalculates the subtotal for the row.

- Rate field:
  - Recalculates the subtotal for the row without changing other fields.

The subtotal is calculated as: subtotal = rate × quantity.
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



/*
document.addEventListener('DOMContentLoaded', () => {
    const clientField = document.querySelector('#jform_client_id');
    const accountContainer = document.querySelector('.account-container');
    const spinner = document.querySelector('.account-loading-spinner');
    const accountWrapper = document.querySelector('.account_id_wrapper');
    const accountField = document.querySelector('#jform_account_id');
    const MIN_SPINNER_TIME = 500;
    let previousClientId = clientField.value;

    function init() {
        const clientId = clientField.value;
        const selectedAccountId = accountField.value;

        if (clientId && clientId !== '0') {
            accountContainer.style.display = 'block';
            accountContainer.style.height = 'auto';
            if (selectedAccountId && selectedAccountId !== '0') {
                accountWrapper.style.display = 'block';
                accountWrapper.classList.add('show');
            } else {
                accountWrapper.style.display = 'none';
                accountWrapper.classList.remove('show');
            }
        } else {
            accountContainer.style.display = 'none';
            spinner.style.display = 'none';
            accountWrapper.style.display = 'none';
            accountWrapper.classList.remove('show');
        }
    }

    function toggleAccountField() {
        const clientId = clientField.value;

        if (!clientId || clientId === '0') {
            hideAccountField();
            previousClientId = clientId;
            return;
        }

        if (previousClientId !== clientId) {
            previousClientId = clientId;
            showSpinner();

            if (accountContainer.style.display !== 'block') {
                slideOpen(accountContainer, () => fetchAndShowAccounts(clientId));
            } else {
                fadeOut(accountWrapper, () => fetchAndShowAccounts(clientId));
            }
        }
    }

    function fetchAndShowAccounts(clientId) {
        const startTime = Date.now();
        fetch(`index.php?option=com_mothership&task=invoice.getAccountsForClient&format=json&client_id=${clientId}`)
            .then(res => res.json())
            .then(data => {
                accountField.innerHTML = '<option value="">Select Account</option>';
                if (data.data) {
                    data.data.forEach(account => {
                        accountField.innerHTML += `<option value="${account.id}">${account.name}</option>`;
                    });
                }
            })
            .catch(console.error)
            .finally(() => {
                const elapsed = Date.now() - startTime;
                setTimeout(() => {
                    hideSpinner();
                    fadeIn(accountWrapper);
                }, Math.max(0, MIN_SPINNER_TIME - elapsed));
            });
    }

    function showSpinner() {
        fadeOut(accountWrapper);
        spinner.style.display = 'block';
        requestAnimationFrame(() => spinner.classList.add('show'));
    }

    function hideSpinner() {
        spinner.classList.remove('show');
        spinner.addEventListener('transitionend', () => spinner.style.display = 'none', { once: true });
    }

    function hideAccountField() {
        fadeOut(accountWrapper);
        slideClose(accountContainer);
        accountField.innerHTML = '<option value="">Select Account</option>';
    }

    function slideOpen(el, callback) {
        el.style.display = 'block';
        el.style.height = '0';
        requestAnimationFrame(() => {
            el.style.height = `${el.scrollHeight}px`;
        });
        el.addEventListener('transitionend', () => {
            el.style.height = '';
            if (callback) callback();
        }, { once: true });
    }

    function slideClose(el) {
        el.style.height = `${el.scrollHeight}px`;
        requestAnimationFrame(() => el.style.height = '0');
        el.addEventListener('transitionend', () => {
            el.style.display = 'none';
            el.style.height = '';
        }, { once: true });
    }

    function fadeIn(el) {
        el.style.display = 'block';
        requestAnimationFrame(() => el.classList.add('show'));
    }

    function fadeOut(el, callback) {
        el.classList.remove('show');
        el.addEventListener('transitionend', () => {
            el.style.display = 'none';
            if (callback) callback();
        }, { once: true });
    }

    init();
    clientField.addEventListener('change', toggleAccountField);
});

*/