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
     *   - The loading accountSpinner (.account-loading-spinner) is also hidden.
     *
     * - When a client is selected:
     *   1. The Account field slides open over 200ms.
     *   2. A loading accountSpinner fades in over 200ms, centered in the account container.
     *   3. An AJAX request is sent to:
     *      /administrator/index.php?option=com_mothership&task=invoice.getAccountsList&client_id={clientId}
     *   4. While waiting, the Account dropdown is hidden.
     *   5. On AJAX success:
     *      - The Account dropdown is cleared and populated with the returned list.
     *      - Each item is an object with { value, text, disable }.
     *      - The accountSpinner fades out (200ms), and the dropdown fades in (200ms).
     *
     * - If the user selects a blank client:
     *   - The Account section fades out and slides closed over 200ms.
     *   - The accountSpinner is reset and hidden.
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

    const clientSelect = $('#jform_client_id');
    const accountWrapper = $('.account_id_wrapper');
    const accountSpinner = $('.account-loading-spinner');
    const accountSelect = $('#jform_account_id');

    function isNewInvoice() {
        return clientSelect.val() === '';
    }

    function revealAccountField(clientId) {}
        // Initial state
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

                    accountSpinner.css({
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


    // On page load
    if (isNewInvoice()) {
        accountWrapper.hide();
        accountSpinner.hide();
    }

    // On client change
    clientSelect.on('change', function () {
        const selectedVal = $(this).val();
        
        if (selectedVal) {
            revealAccountField(selectedVal);
        }
        else {
            hideAccountField();
        }
    });
});