<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

?>
<h1>Invoices</h1>
<table class="table" id="invoicetable">
    <thead>
        <tr>
            <th>PDF</th>
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
                <td colspan="7">No invoices found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->invoices as $invoice) : ?>
            <tr>
                <td>    
                    <a href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.downloadPdf&id=' . $invoice->id); ?>" target="_blank">PDF</a>
                </td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoice->id); ?>"><?php echo $invoice->number; ?></a></td>
                <td><?php echo $invoice->account_name; ?></td>
                <td>$<?php echo number_format($invoice->total, 2); ?></td>
                <td><?php echo $invoice->status; ?></td>
                <td>
                    <?php if($invoice->status === 'Opened' || $invoice->status === 'Late'): ?>
                    <?php
                    $dueDate = new DateTime($invoice->due_date, new DateTimeZone('UTC'));
                    $dueDate->setTime(23, 59, 59);
                    
                    $currentDate = new DateTime('now', new DateTimeZone('UTC'));
                    $interval = $currentDate->diff($dueDate);
                    echo "Due in {$interval->days} days";
                    ?>
                    <?php endif; ?>
                </td>
                
                <td>
                    <ul>
                        <li><a href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.edit&id=' . $invoice->id); ?>">View</a></li>
                        <?php if($invoice->status === 'Opened' || $invoice->status === 'Late'): ?>
                        <li><a href="<?php echo Route::_("index.php?option=com_mothership&task=invoice.payment&id={$invoice->id}"); ?>">Pay</a></li>
                        <?php endif; ?>
                    </ul>
                    
                    
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
