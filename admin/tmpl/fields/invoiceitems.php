<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2025 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

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
document.getElementById('add-invoice-item').addEventListener('click', function() {
    var table = document.getElementById('invoice-items-table').getElementsByTagName('tbody')[0];
    var rowCount = table.rows.length;
    var row = table.insertRow(rowCount);
    row.className = 'invoice-item-row';

    var cell1 = row.insertCell(0);
    cell1.style.width = '1%';
    cell1.innerHTML = '<input type="checkbox" name="jform[items][' + rowCount + '][selected]" value="1">';

    var cell2 = row.insertCell(1);
    cell2.innerHTML = '<input type="text" name="jform[items][' + rowCount + '][name]" value="" class="form-control">';

    var cell3 = row.insertCell(2);
    cell3.innerHTML = '<input type="text" name="jform[items][' + rowCount + '][description]" value="" class="form-control">';

    var cell4 = row.insertCell(3);
    cell4.style.width = '1%';
    cell4.innerHTML = '<input type="number" step="1" name="jform[items][' + rowCount + '][hours]" value="0" class="form-control">';

    var cell5 = row.insertCell(4);
    cell5.style.width = '1%';
    cell5.innerHTML = '<input type="number" step="1" name="jform[items][' + rowCount + '][minutes]" value="0" class="form-control">';

    var cell6 = row.insertCell(5);
    cell6.style.width = '1%';
    cell6.innerHTML = '<input type="number" step="0.01" name="jform[items][' + rowCount + '][quantity]" value="1" class="form-control">';

    var cell7 = row.insertCell(6);
    cell7.style.width = '1%';
    cell7.innerHTML = '<input type="number" step="0.01" name="jform[items][' + rowCount + '][rate]" value="0" class="form-control">';

    var cell8 = row.insertCell(7);
    cell8.style.width = '1%';
    cell8.innerHTML = '<input type="number" step="0.01" name="jform[items][' + rowCount + '][subtotal]" value="0" class="form-control" readonly>';

    var cell9 = row.insertCell(8);
    cell9.style.width = '1%';
    cell9.innerHTML = '<button type="button" class="btn btn-danger remove-row">×</button>';

    // Add event listener to the new remove button
    cell9.querySelector('.remove-row').addEventListener('click', function() {
        table.deleteRow(row.rowIndex - 1);
    });
});

// Add event listeners to existing remove buttons
document.querySelectorAll('.remove-row').forEach(function(button) {
    button.addEventListener('click', function() {
        var row = button.closest('tr');
        row.parentNode.removeChild(row);
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('#invoice-items-table tbody');

    new Sortable(tableBody, {
        handle: '.drag-handle',
        animation: 150
    });

    function ensureAtLeastOneRow() {
        if (tableBody.rows.length === 0) {
            addNewRow();
        }
    }

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

</script></script>