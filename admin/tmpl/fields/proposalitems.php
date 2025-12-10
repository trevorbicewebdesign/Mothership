<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\ProposalHelper;

/**
 * @phpstan-import-type ApplicationFactory from \Joomla\CMS\Factory
 * @phpstan-import-type LanguageText from \Joomla\CMS\Language\Text
 */

defined('_JEXEC') or die;

// There is deff a better way to handle this, but for now this is a quick fix
$app   = Factory::getApplication();
$input = $app->input;
$id    = $input->getInt('id', 0);

try {
    $proposal = ProposalHelper::getProposal($id);
    $isLocked = $proposal->locked ?? false;
} catch (Exception $e) {
    $isLocked = false;
}

$field = $displayData['field'];
$items = $field->value ?? [];
?>
<style>
    .has-danger .invalid-feedback {
        display: block;
    }
</style>

<table class="table table-striped" id="proposal-items-table">
    <thead>
        <tr>
            <th width="1%"></th>
            <th><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_NAME'); ?></th>
            <th><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_DESCRIPTION'); ?></th>
            <th><?php echo "Type" ?></th>
            <th width="1%"><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_TIME_LOW'); ?> (HH:MM)</th>
            <th width="1%"><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_TIME'); ?> (HH:MM)</th>            
            <th width="1%"><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_QUANTITY_LOW'); ?></th>
            <th width="1%"><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_QUANTITY'); ?></th>
            <th width="6%"><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_RATE'); ?></th>
            <th width="1%"><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_SUBTOTAL_LOW'); ?></th>
            <th width="1%"><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_SUBTOTAL'); ?></th>
            <th width="1%"><?php echo Text::_('COM_MOTHERSHIP_PROPOSAL_ITEM_ACTIONS'); ?></th>
        </tr>
    </thead>

    <tbody>
        <?php if (!empty($items)) : ?>
            <?php foreach ($items as $index => $item) : ?>
                <tr class="proposal-item-row">
                    <td class="drag-handle"><?php if (!$isLocked) : ?>☰<?php endif; ?></td>

                    <td>
                        <div class="form-group">
                            <input type="text"
                                   name="jform[items][<?php echo $index; ?>][name]"
                                   required="required"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>"
                                   <?php if($isLocked): ?>disabled<?php endif; ?>>

                            <div class="invalid-feedback">Please provide an item name.</div>
                        </div>
                    </td>

                    <td>
                        <input type="text"
                               name="jform[items][<?php echo $index; ?>][description]"
                               class="form-control"
                               value="<?php echo htmlspecialchars($item['description'] ?? ''); ?>"
                               <?php if($isLocked): ?>disabled<?php endif; ?>>
                    </td>
                    
                    <td>
                        <select name="jform[items][<?php echo $index; ?>][type]" class="form-control" <?php if($isLocked): ?>disabled<?php endif; ?>>
                            <option value="hourly" <?php echo (isset($item['type']) && $item['type'] === 'hourly') ? 'selected' : ''; ?>>Hourly</option>
                            <option value="fixed" <?php echo (isset($item['type']) && $item['type'] === 'fixed') ? 'selected' : ''; ?>>Fixed</option>
                        </select>                        
                    </td>

                    <td>
                        <input type="text"
                               name="jform[items][<?php echo $index; ?>][time_low]"
                               class="form-control"
                               placeholder="00:00"
                               value="<?php echo htmlspecialchars($item['time_low'] ?? ''); ?>"
                               <?php if($isLocked): ?>disabled<?php endif; ?>>
                    </td>

                      

                    <td>
                        <input type="text"
                               name="jform[items][<?php echo $index; ?>][time]"
                               class="form-control"
                               placeholder="00:00"
                               value="<?php echo htmlspecialchars($item['time'] ?? ''); ?>"
                               <?php if($isLocked): ?>disabled<?php endif; ?>>
                    </td>

                    <td>
                        <input type="number"
                               step="0.01"
                               name="jform[items][<?php echo $index; ?>][quantity_low]"
                               class="form-control"
                               value="<?php echo (float)($item['quantity_low'] ?? 1); ?>"
                               <?php if($isLocked): ?>disabled<?php endif; ?>>
                    </td>

                     <td>
                        <input type="number"
                               step="0.01"
                               name="jform[items][<?php echo $index; ?>][quantity]"
                               class="form-control"
                               value="<?php echo (float)($item['quantity'] ?? 1); ?>"
                               <?php if($isLocked): ?>disabled<?php endif; ?>>
                    </td>

                    <td>
                        <input type="number"
                               step="0.01"
                               name="jform[items][<?php echo $index; ?>][rate]"
                               class="form-control"
                               value="<?php echo (float)($item['rate'] ?? 0); ?>"
                               <?php if($isLocked): ?>disabled<?php endif; ?>>
                    </td>

                     <td>
                        <input type="number"
                               step="0.01"
                               name="jform[items][<?php echo $index; ?>][subtotal_low]"
                               class="form-control"
                               readonly
                               value="<?php echo (float)($item['subtotal_low'] ?? 0); ?>"
                               <?php if($isLocked): ?>disabled<?php endif; ?>>
                    </td>

                    <td>
                        <input type="number"
                               step="0.01"
                               name="jform[items][<?php echo $index; ?>][subtotal]"
                               class="form-control"
                               readonly
                               value="<?php echo (float)($item['subtotal'] ?? 0); ?>"
                               <?php if($isLocked): ?>disabled<?php endif; ?>>
                    </td>

                   

                    <td>
                        <?php if (!$isLocked) : ?>
                            <button type="button" class="btn btn-danger remove-row">×</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php else : ?>

            <tr class="proposal-item-row">
                <td class="drag-handle">☰</td>

                <td>
                    <div class="form-group">
                        <input type="text"
                               name="jform[items][0][name]"
                               required="required"
                               class="form-control"
                               value=""
                               <?php if ($isLocked): ?>disabled<?php endif; ?>>
                        <div class="invalid-feedback">Please provide an item name.</div>
                    </div>
                </td>

                <td>
                    <input type="text"
                           name="jform[items][0][description]"
                           class="form-control"
                           <?php if($isLocked): ?>disabled<?php endif; ?>>
                </td>

                <td>
                    <input type="text"
                           name="jform[items][0][time_low]"
                           class="form-control"
                           placeholder="00:00"
                           value=""
                           <?php if ($isLocked): ?>disabled<?php endif; ?>>
                </td>

                <td>
                    <input type="text"
                           name="jform[items][0][time]"
                           class="form-control"
                           placeholder="00:00"
                           value=""
                           <?php if ($isLocked): ?>disabled<?php endif; ?>>
                </td>

                <td>
                    <input type="number"
                           step="0.01"
                           name="jform[items][0][quantity]"
                           class="form-control"
                           value="1"
                           <?php if ($isLocked): ?>disabled<?php endif; ?>>
                </td>

                <td>
                    <input type="number"
                           step="0.01"
                           name="jform[items][0][rate]"
                           class="form-control"
                           value="0"
                           <?php if ($isLocked): ?>disabled<?php endif; ?>>
                </td>

                <td>
                    <input type="number"
                           step="0.01"
                           name="jform[items][0][low_total]"
                           class="form-control"
                           readonly
                           value="0"
                           <?php if ($isLocked): ?>disabled<?php endif; ?>>
                </td>

                <td>
                    <input type="number"
                           step="0.01"
                           name="jform[items][0][high_total]"
                           class="form-control"
                           readonly
                           value="0"
                           <?php if ($isLocked): ?>disabled<?php endif; ?>>
                </td>

                <td>
                    <?php if (!$isLocked) : ?>
                        <button type="button" class="btn btn-danger remove-row">×</button>
                    <?php endif; ?>
                </td>
            </tr>

        <?php endif; ?>
    </tbody>
</table>

<?php if (!$isLocked) : ?>
    <button type="button" class="btn btn-success" id="add-proposal-item">
        <?php echo Text::_('COM_MOTHERSHIP_ADD_ITEM'); ?>
    </button>
<?php endif; ?>


<script type="text/javascript">
jQuery(function ($) {
    const $tableBody = $('#proposal-items-table tbody');
    const $form      = $('#adminForm');

    /**
     * Convert "HH:MM" to decimal hours.
     * If it's not HH:MM, try to treat the value as a simple number (e.g. "1.5").
     */
    function parseTimeToHours(value) {
        if (!value) {
            return 0;
        }

        const str   = String(value).trim();
        const match = str.match(/^(\d{1,3}):(\d{2})$/);

        if (match) {
            const hours   = parseInt(match[1], 10) || 0;
            const minutes = parseInt(match[2], 10) || 0;
            return hours + (minutes / 60);
        }

        const n = parseFloat(str);
        return isNaN(n) ? 0 : n;
    }

    function toNumber(value, fallback = 0) {
        const n = parseFloat(value);
        return isNaN(n) ? fallback : n;
    }

    /**
     * Recalculate low / high totals for a single row based on:
     *  - time_low (HH:MM) & time (HH:MM – high)
     *  - quantity_low & quantity
     *  - rate
     *
     * Supports both field-name styles:
     *  - subtotal / subtotal_low
     *  - low_total / high_total
     */
    function recalcRowTotals($row) {
        // Quantities
        const qtyHigh = toNumber(
            $row.find('input[name$="[quantity]"]').val(),
            1
        );

        const $qtyLowInput = $row.find('input[name$="[quantity_low]"]');
        const qtyLow = $qtyLowInput.length
            ? toNumber($qtyLowInput.val(), qtyHigh)
            : qtyHigh;

        // Times (in hours)
        const timeLowRaw  = $row.find('input[name$="[time_low]"]').val();
        const timeHighRaw = $row.find('input[name$="[time]"]').val();

        let timeLow  = parseTimeToHours(timeLowRaw);
        let timeHigh = parseTimeToHours(timeHighRaw);

        // Reasonable fallbacks
        if (timeHigh === 0 && timeLow > 0) {
            timeHigh = timeLow;
        }
        if (timeLow === 0 && timeHigh > 0) {
            timeLow = timeHigh;
        }

        const rate = toNumber(
            $row.find('input[name$="[rate]"]').val(),
            0
        );

        const lowTotal  = rate * timeLow  * qtyLow;
        const highTotal = rate * timeHigh * qtyHigh;

        // Low total field can be either subtotal_low or low_total
        const $lowTotalField = $row.find(
            'input[name$="[subtotal_low]"], input[name$="[low_total]"]'
        );
        if ($lowTotalField.length) {
            $lowTotalField.val(lowTotal.toFixed(2));
        }

        // High total field can be either subtotal or high_total
        const $highTotalField = $row.find(
            'input[name$="[subtotal]"], input[name$="[high_total]"]'
        );
        if ($highTotalField.length) {
            $highTotalField.val(highTotal.toFixed(2));
        }
    }

    function addNewRow() {
        const rowCount = $tableBody.find('tr').length;
        const $newRow  = $tableBody.find('tr').first().clone(true, true);

        $newRow.find('input').each(function () {
            const $input = $(this);
            const oldName = $input.attr('name');

            if (oldName) {
                const newName = oldName.replace(/\[\d+\]/, '[' + rowCount + ']');
                $input.attr('name', newName);
            }

            if ($input.prop('readonly')) {
                // Totals default to 0.00
                $input.val('0.00');
            } else {
                $input.val('');
            }

            $input.removeClass('is-invalid').removeAttr('aria-invalid');
        });

        $newRow.find('.invalid-feedback').text('Please provide an item name.');
        $tableBody.append($newRow);

        // Initialize totals for the freshly added row
        recalcRowTotals($newRow);
    }

    function validateRows() {
        let hasErrors = false;

        $tableBody.find('tr').each(function () {
            const $row      = $(this);
            const $input    = $row.find('input[name$="[name]"]');
            const $feedback = $input.next('.invalid-feedback');

            if ($input.val().trim() === '') {
                $input.addClass('is-invalid').attr('aria-invalid', 'true');
                $feedback.text('Item Name is required.');
                hasErrors = true;
            } else {
                $input.removeClass('is-invalid').removeAttr('aria-invalid');
                $feedback.text('');
            }
        });

        return !hasErrors;
    }

    // Add new row
    $('#add-proposal-item').on('click', function () {
        addNewRow();
    });

    // Remove row
    $tableBody.on('click', '.remove-row', function () {
        if ($tableBody.find('tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('At least one item is required.');
        }
    });

    // Live error clearing for item name
    $tableBody.on('input', 'input[name$="[name]"]', function () {
        const $input    = $(this);
        const $feedback = $input.next('.invalid-feedback');

        if ($input.val().trim() !== '') {
            $input.removeClass('is-invalid').removeAttr('aria-invalid');
            $feedback.text('');
        }
    });

    // Recalculate totals whenever any relevant field changes
    $tableBody.on('input change',
        'input[name$="[time]"], ' +
        'input[name$="[time_low]"], ' +
        'input[name$="[quantity]"], ' +
        'input[name$="[quantity_low]"], ' +
        'input[name$="[rate]"]',
        function () {
            const $row = $(this).closest('tr');
            recalcRowTotals($row);
        }
    );

    // Validate rows on form submit
    if ($form.length) {
        $form.on('submit', function (e) {
            if (!validateRows()) {
                e.preventDefault();
                const $firstError = $tableBody.find('input.is-invalid').first();
                if ($firstError.length) {
                    $firstError.focus();
                }
            } else {
                // Make sure everything is up-to-date before submit
                $tableBody.find('tr').each(function () {
                    recalcRowTotals($(this));
                });
            }
        });
    }

    // Initialize totals on page load for existing items
    $tableBody.find('tr').each(function () {
        recalcRowTotals($(this));
    });
});
</script>

