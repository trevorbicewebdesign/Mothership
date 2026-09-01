<?php
/**
 * @package     Mothership
 * @subpackage  Plugin.Mothership-Payment.Zelle
 * @copyright   ...
 * @license     ...
 */

defined('_JEXEC') or die;

$plugin = JPluginHelper::getPlugin('mothership-payment', 'zelle');
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

<h3>Zelle Payment Instructions</h3>

<p><strong>Invoice #<?= $invoiceNumber ?></strong></p>

<p>
  Please send your Zelle payment to: <strong><?= htmlspecialchars($pluginParams->get('zelle_email', '')); ?></strong>
</p>

<p>
  In the Zelle note/memo, include your invoice number <strong>#<?= $invoiceNumber ?></strong> so we can match your payment.
</p>

<p>
  After completing the payment, click the button below to let us know.
  Your payment will be marked as <code>Pending</code> until it is manually verified by an administrator.
</p>
