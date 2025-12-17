<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Layout\FileLayout;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

$proposal = isset($this->item) ? $this->item : null;
$client = ClientHelper::getClient($proposal->client_id);
$account = AccountHelper::getAccount($proposal->account_id);
$business = MothershipHelper::getMothershipOptions();

if (!$proposal) {
    echo '<div class="alert alert-warning">Invoice not found.</div>';
    return;
}

$layout = new FileLayout('proposal-pdf', JPATH_ROOT . '/components/com_mothership/layouts');
echo $layout->render([
    'proposal' => $proposal,
    'client' => $client,
    'account' => $account,
    'business' => $business
]);