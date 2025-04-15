<?php

use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;

defined('_JEXEC') or die;

// There is deff a better way to handle this, but for now this is a quick fix
$app = JFactory::getApplication();
$input = $app->input;
$id = $input->getInt('id', 0);
$invoice = InvoiceHelper::getInvoice($id);
$isLocked = $invoice->locked ?? false;

$field = $displayData['field'];
$items = $field->value ?? [];
?>
<style>
    .has-danger .invalid-feedback{
        display: block;
    }
</Style>
<table class="table table-striped" id="invoice-items-table">
    <thead>
        <tr>
            <th width="1%"></th>
            <th><?php echo JText::_('COM_MOTHERSHIP_ITEM_NAME'); ?></th>
            <th><?php echo JText::_('COM_MOTHERSHIP_ITEM_DESCRIPTION'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_HOURS'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_MINUTES'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_QUANTITY'); ?></th>
            <th width="6%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_RATE'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_SUBTOTAL'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_ACTIONS'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($items)) : ?>
            <?php foreach ($items as $index => $item) : ?>
                <tr class="invoice-item-row">
                    <td class="drag-handle"><?php if (!$isLocked) : ?>☰<?php endif; ?></td>
                    <td>
                        <div class="form-group">
                        <input type="text" name="jform[items][<?php echo $index; ?>][name]" required="required" class="form-control" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" <?php if($isLocked): ?>disabled="true"<?php endif; ?>>
                            <div class="invalid-feedback">Please provide an item name.</div>
                        </div>
                    </td>
                    <td><input type="text" name="jform[items][<?php echo $index; ?>][description]" class="form-control" value="<?php echo htmlspecialchars($item['description'] ?? ''); ?>" <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                    <td><input type="number" name="jform[items][<?php echo $index; ?>][hours]" class="form-control" value="<?php echo (float)($item['hours'] ?? 0); ?>" <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                    <td><input type="number" name="jform[items][<?php echo $index; ?>][minutes]" class="form-control" value="<?php echo (float)($item['minutes'] ?? 0); ?>" <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                    <td><input type="number" step="0.01" name="jform[items][<?php echo $index; ?>][quantity]" class="form-control" value="<?php echo (float)($item['quantity'] ?? 1); ?>" <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                    <td><input type="number" step="0.01" name="jform[items][<?php echo $index; ?>][rate]" class="form-control" value="<?php echo (float)($item['rate'] ?? 0); ?>" <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                    <td><input type="number" step="0.01" name="jform[items][<?php echo $index; ?>][subtotal]" class="form-control" readonly value="<?php echo (float)($item['subtotal'] ?? 0); ?>" <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                    
                    <td><?php if (!$isLocked) : ?><button type="button" class="btn btn-danger remove-row">×</button><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr class="invoice-item-row">
                <td class="drag-handle">☰</td>
                <td>
                    <div class="form-group">
                    <input type="text" name="jform[items][0][name]" required="required" class="form-control"  <?php if($isLocked): ?>disabled="true"<?php endif; ?>>
                        <div class="invalid-feedback">Please provide an item name.</div>
                    </div>
                </td>
                <td><input type="text" name="jform[items][0][description]" class="form-control"  <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                <td><input type="number" name="jform[items][0][hours]" class="form-control" value="0"  <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                <td><input type="number" name="jform[items][0][minutes]" class="form-control" value="0"  <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                <td><input type="number" step="0.01" name="jform[items][0][quantity]" class="form-control" value="1"  <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                <td><input type="number" step="0.01" name="jform[items][0][rate]" class="form-control" value="0"  <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                <td><input type="number" step="0.01" name="jform[items][0][subtotal]" class="form-control" readonly value="0"  <?php if($isLocked): ?>disabled="true"<?php endif; ?>></td>
                <td><?php if (!$isLocked) : ?><button type="button" class="btn btn-danger remove-row">×</button><?php endif; ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if (!$isLocked) : ?>
<button type="button" class="btn btn-success" id="add-invoice-item">
    <?php echo JText::_('COM_MOTHERSHIP_ADD_ITEM'); ?>
</button>
<?php endif; ?>

<script type="text/javascript">
jQuery(document).ready(function ($) {
    const $tableBody = $('#invoice-items-table tbody');
    const $form = $('#adminForm');

    function addNewRow() {
        const rowCount = $tableBody.find('tr').length;
        const $newRow = $tableBody.find('tr').first().clone(true, true);

        $newRow.find('input').each(function () {
            const $input = $(this);
            const oldName = $input.attr('name');
            const newName = oldName.replace(/\[\d+\]/, `[${rowCount}]`);
            $input.attr('name', newName);
            $input.val($input.prop('readonly') ? '0.00' : '');
            $input.removeClass('is-invalid').removeAttr('aria-invalid');
        });

        $newRow.find('.invalid-feedback').text('');
        $tableBody.append($newRow);
    }

    function validateRows() {
        let hasErrors = false;
        $tableBody.find('tr').each(function () {
            const $row = $(this);
            const $input = $row.find('input[name$="[name]"]');
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
    $('#add-invoice-item').on('click', function () {
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

    // Live error clearing
    $tableBody.on('input', 'input[name$="[name]"]', function () {
        const $input = $(this);
        if ($input.val().trim() !== '') {
            $input.removeClass('is-invalid').removeAttr('aria-invalid');
            $input.next('.invalid-feedback').text('');
        }
    });

});
</script>
