<?php
defined('_JEXEC') or die;

$field = $displayData['field'];
$items = $field->value ?? [];
?>

<table class="table table-striped" id="invoice-items-table">
    <thead>
        <tr>
            <th width="1%"></th> <!-- Drag handle -->
            <th><?php echo JText::_('COM_MOTHERSHIP_ITEM_NAME'); ?></th>
            <th><?php echo JText::_('COM_MOTHERSHIP_ITEM_DESCRIPTION'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_HOURS'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_MINUTES'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_QUANTITY'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_RATE'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_SUBTOTAL'); ?></th>
            <th width="1%"><?php echo JText::_('COM_MOTHERSHIP_ITEM_ACTIONS'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($items)) : ?>
            <?php foreach ($items as $index => $item) : ?>
                <tr class="invoice-item-row">
                    <td class="drag-handle">☰</td>
                    <td><input type="text" name="jform[items][<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" class="form-control"></td>
                    <td><input type="text" name="jform[items][<?php echo $index; ?>][description]" value="<?php echo htmlspecialchars($item['description'] ?? ''); ?>" class="form-control"></td>
                    <td><input type="number" step="1" name="jform[items][<?php echo $index; ?>][hours]" value="<?php echo (float)($item['hours'] ?? 0); ?>" class="form-control"></td>
                    <td><input type="number" step="1" name="jform[items][<?php echo $index; ?>][minutes]" value="<?php echo (float)($item['minutes'] ?? 0); ?>" class="form-control"></td>
                    <td><input type="number" step="0.01" name="jform[items][<?php echo $index; ?>][quantity]" value="<?php echo (float)($item['quantity'] ?? 1); ?>" class="form-control"></td>
                    <td><input type="number" step="0.01" name="jform[items][<?php echo $index; ?>][rate]" value="<?php echo (float)($item['rate'] ?? 0); ?>" class="form-control"></td>
                    <td><input type="number" step="0.01" name="jform[items][<?php echo $index; ?>][subtotal]" value="<?php echo (float)($item['subtotal'] ?? 0); ?>" class="form-control" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-row">×</button></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr class="invoice-item-row">
                <td class="drag-handle">☰</td>
                <td><input type="text" name="jform[items][0][name]" value="" class="form-control"></td>
                <td><input type="text" name="jform[items][0][description]" value="" class="form-control"></td>
                <td><input type="number" step="1" name="jform[items][0][hours]" value="0" class="form-control"></td>
                <td><input type="number" step="1" name="jform[items][0][minutes]" value="0" class="form-control"></td>
                <td><input type="number" step="0.01" name="jform[items][0][quantity]" value="1" class="form-control"></td>
                <td><input type="number" step="0.01" name="jform[items][0][rate]" value="0" class="form-control"></td>
                <td><input type="number" step="0.01" name="jform[items][0][subtotal]" value="0" class="form-control" readonly></td>
                <td><button type="button" class="btn btn-danger remove-row">×</button></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<button type="button" class="btn btn-success" id="add-invoice-item"><?php echo JText::_('COM_MOTHERSHIP_ADD_ITEM'); ?></button>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('#invoice-items-table tbody');

    new Sortable(tableBody, {
        handle: '.drag-handle',
        animation: 150
    });

    function addNewRow() {
        const rowCount = tableBody.rows.length;
        const newRow = tableBody.rows[0].cloneNode(true);

        newRow.querySelectorAll('input').forEach(input => {
            const name = input.getAttribute('name').replace(/\[\d+\]/, `[${rowCount}]`);
            input.setAttribute('name', name);
            input.value = (input.type === 'number') ? '0' : '';
        });

        tableBody.appendChild(newRow);
    }

    document.getElementById('add-invoice-item').addEventListener('click', addNewRow);

    tableBody.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-row')) {
            const row = event.target.closest('tr');
            if (tableBody.rows.length > 1) {
                row.remove();
            } else {
                alert('At least one item is required.');
            }
        }
    });
});
</script>
