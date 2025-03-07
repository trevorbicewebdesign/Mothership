<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
?>
<h1>Your Invoices</h1>
<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Due Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($this->invoices as $invoice) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoice->id); ?>"><?php echo $invoice->number; ?></a></td>
                <td>$<?php echo number_format($invoice->total, 2); ?></td>
                <td><?php echo $invoice->status; ?></td>
                <td><?php echo $invoice->due; ?></td>
                <td>
                    <a href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.downloadPdf&id=' . $invoice->id); ?>">PDF</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
