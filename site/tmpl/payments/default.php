<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

?>
<h1>Payments</h1>
<table class="table paymentsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Account</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Fee Amount</th>
            <th>Payment Method</th>
            <th>Transaction Id</th>
            <th>Invoices</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->payments)) : ?>
            <tr>
                <td colspan="7">No payments found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->payments as $payment) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=payment&id=' . $payment->id); ?>"><?php echo $payment->number; ?></a></td>
                <td><?php echo $payment->account_name; ?></td>
                <td>$<?php echo number_format($payment->amount, 2); ?></td>
                <td><?php echo $payment->status; ?></td>
                <td>$<?php echo number_format($payment->fee_amount, 2); ?></td>
                <td><?php echo $payment->payment_method; ?></td>
                <td><?php echo $payment->transaction_id; ?></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $payment->invoice_ids); ?>" ><?php echo $payment->invoice_ids; ?></a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
