<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

$status_levels = [
    1 => 'Draft',
    2 => 'Opened',
    3 => 'Late',
    4 => 'Paid'
];
?>
<h1>Invoices</h1>
<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Account</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Due Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->invoices)) : ?>
            <tr>
                <td colspan="6">No invoices found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->invoices as $invoice) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoice->id); ?>"><?php echo $invoice->number; ?></a></td>
                <td><?php echo $invoice->account_name; ?></td>
                <td>$<?php echo number_format($invoice->total, 2); ?></td>
                <td><?php echo $status_levels[$invoice->status]; ?></td>
                <td><?php echo $invoice->due; ?></td>
                <td>    
                    <a href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.downloadPdf&id=' . $invoice->id); ?>" target="_blank">PDF</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
