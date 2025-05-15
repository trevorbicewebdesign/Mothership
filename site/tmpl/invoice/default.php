<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Layout\FileLayout;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

$invoice = isset($this->item) ? $this->item : null;
$client = ClientHelper::getClient($invoice->client_id);
$account = AccountHelper::getAccount($invoice->account_id);
$business = MothershipHelper::getMothershipOptions();

if (!$invoice) {
    echo '<div class="alert alert-warning">Invoice not found.</div>';
    return;
}

$layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
echo $layout->render([
    'invoice' => $invoice,
    'client' => $client,
    'account' => $account,
    'business' => $business
]);