<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Layout\FileLayout;

$invoice = isset($this->item) ? $this->item : null;

if (!$invoice) {
    echo '<div class="alert alert-warning">Invoice not found.</div>';
    return;
}

$layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
echo $layout->render(['invoice' => $invoice]);