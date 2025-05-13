<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;

$admin_fname   = $displayData['admin_fname'];

$payment = $displayData['payment'];
$invoice = $displayData['invoice'];
$client = $displayData['client'];

$confirm_link  = $displayData['confirm_link'] ?? '#';
$view_link = $displayData['view_link'] ?? '#';
?>
<p>Hello <?= $admin_fname; ?>,</p>
<p>A new <?php echo $payment->payment_method; ?> payment for <?php echo "$".number_format($invoice->amount, 2); ?> has been initiated by <?php echo $client->name; ?></p>
<p>Please mark this payment as `Confirmed` when you receive it.</p>
<p>Click <a href="<?php echo $view_link; ?>">here</a> to review the payment.</p>
