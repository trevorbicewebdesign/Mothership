<?php
/**
 * @package     Mothership
 * @subpackage  Plugin.Mothership-Payment.PayByCheck
 * @copyright   ...
 * @license     ...
 */

defined('_JEXEC') or die;

$plugin = JPluginHelper::getPlugin('mothership-payment', 'paybycheck');
$pluginParams = new JRegistry($plugin->params);

/** @var array $displayData */
$invoiceId = (int) ($displayData['invoiceId'] ?? 0);
$paymentId = (int) ($displayData['id'] ?? 0);

// Show the human-facing invoice number, never the internal id.
$invoiceNumber = $displayData['invoiceNumber'] ?? '';
if ($invoiceNumber === '' && $invoiceId) {
    $db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
    $db->setQuery(
        $db->getQuery(true)
            ->select($db->quoteName('number'))
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('id') . ' = ' . $invoiceId)
    );
    $invoiceNumber = (string) $db->loadResult();
}
$invoiceNumber = htmlspecialchars($invoiceNumber !== '' ? $invoiceNumber : (string) $invoiceId);
?>

<h3>Pay By Check Payment Instructions</h3>

<p><strong>Invoice #<?= $invoiceNumber ?></strong></p>

<p>
  Please make your check payable to: <strong><?= htmlspecialchars($pluginParams->get('checkpayee', '')); ?></strong>
</p>

<p>
  Be sure to include the invoice number <strong>#<?= $invoiceNumber ?></strong> in the memo line of the check.
</p>

<p>
  Once your check has been mailed, please click the button below to let us know.  
  Your payment status will be marked as <code>Pending</code> until an administrator confirms receipt.
</p>

