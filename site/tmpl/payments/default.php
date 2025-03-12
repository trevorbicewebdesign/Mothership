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
<h1>Payments</h1>
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
        <?php if(empty($this->payments)) : ?>
            <tr>
                <td colspan="6">No payments found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->payments as $payment) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=payment&id=' . $payment->id); ?>"><?php echo $payment->number; ?></a></td>
                <td><?php echo $payment->account_name; ?></td>
                <td>$<?php echo number_format($payment->total, 2); ?></td>
                <td><?php echo $status_levels[$payment->status]; ?></td>
                <td>
                    <?php
                    $dueDate = new DateTime($payment->due_date);
                    $currentDate = new DateTime();
                    $interval = $currentDate->diff($dueDate);
                    echo 'Due in ' . $interval->days . ' days';
                    ?>
                </td>
                
                <td>
                    <ul>
                        <li><a href="<?php echo Route::_('index.php?option=com_mothership&task=payment.edit&id=' . $payment->id); ?>">View</a></li>
                        <li><a href="<?php echo Route::_("index.php?option=com_mothership&task=payment.payment&id={$payment->id}"); ?>">Pay</a></li>
                    </ul>
                    
                    
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
