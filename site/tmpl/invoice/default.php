<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Layout\FileLayout;
use Mothership\Component\Mothership\Site\Helper\ClientHelper;
use Mothership\Component\Mothership\Site\Helper\AccountHelper;
use Mothership\Component\Mothership\Site\Helper\MothershipHelper;

$invoice = isset($this->item) ? $this->item : null;
$client = ClientHelper::getClient($invoice->client_id);
$account = AccountHelper::getAccount($invoice->account_id);
$business = MothershipHelper::getMothesrshipOptions();

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