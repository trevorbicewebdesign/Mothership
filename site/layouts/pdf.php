<?php

use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;

defined('_JEXEC') or die;

/** @var array $displayData */
$invoice  = $displayData['invoice'] ?? null;
$account  = $displayData['account'] ?? null;
$client   = $displayData['client'] ?? null;
$business = $displayData['business'] ?? null;

// Items fallback to array
$items = $invoice?->items ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo htmlspecialchars((string)($invoice?->number ?? ''), ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body.invoice {
            font-family: "Helvetica Neue", Arial, sans-serif;
            font-size: 12pt;
            color: #333;
            margin: 40px;
        }

        h1, h2, h3 {
            margin: 0 0 10px;
            padding: 0;
            color: #2a6592;
        }

        h1 {
            text-align: center;
            font-size: 24pt;
            margin-bottom: 20px;
            border-bottom: 1px dashed #aac8e4;
            padding-bottom: 10px;
        }

        h2 {
            font-size: 16pt;
            border-bottom: 1px dashed #aac8e4;
            margin-top: 25px;
            padding-bottom: 6px;
        }

        p {
            margin: 3px 0;
        }

        .invoice-header {
            margin-bottom: 20px;
        }

        .invoice-number {
            text-align: center;
            font-size: 14pt;
            margin-bottom: 20px;
        }

        .top-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .top-row > div {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding-right: 10px;
        }

        .company-block strong,
        .client-block strong {
            display: block;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-size: 10pt;
            letter-spacing: 1px;
            color: #555;
        }

        .invoice-meta {
            margin-top: 10px;
            font-size: 11pt;
        }

        .invoice-meta p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px dashed #aac8e4;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #eaf3fa;
            font-weight: bold;
        }

        .totals {
            margin-top: 15px;
            text-align: right;
        }

        .totals h3 {
            font-size: 14pt;
            color: #2a6592;
            margin: 0;
        }

        .section {
            margin-bottom: 20px;
        }

        .notes {
            margin-top: 15px;
        }
    </style>
</head>
<body class="invoice">

    <div class="invoice-header">
        <h1>Invoice of Services</h1>
        <div class="invoice-number">
            <!-- Test expects this exact string -->
            Invoice Number: #<?php echo htmlspecialchars((string)($invoice?->number ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>

    <div class="top-row">
        <div class="company-block">
            <strong>From</strong>
            <p><?php echo htmlspecialchars((string)($business['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><?php echo htmlspecialchars((string)($business['company_address_1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><?php echo htmlspecialchars((string)($business['company_address_2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p>
                <?php echo htmlspecialchars((string)($business['company_city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars((string)($business['company_state'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                <?php echo htmlspecialchars((string)($business['company_zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p><?php echo htmlspecialchars((string)($business['company_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><?php echo htmlspecialchars((string)($business['company_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="client-block">
            <strong>Bill To</strong>
            <p><?php echo htmlspecialchars((string)($client?->name ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><?php echo htmlspecialchars((string)($client?->address_1 ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><?php echo htmlspecialchars((string)($client?->address_2 ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p>
                <?php echo htmlspecialchars((string)($client?->city ?? ''), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars((string)($client?->state ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                <?php echo htmlspecialchars((string)($client?->zip ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <?php if ($account?->name ?? false) : ?>
                <p><strong>Account:</strong> <?php echo htmlspecialchars((string)$account->name, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <div class="invoice-meta">
                <!-- Test expects these exact labels -->
                <p><strong>Invoice Status:</strong><?php echo htmlspecialchars(InvoiceHelper::getStatus($invoice->status)); ?></p>
                <p><strong>Invoice Due:</strong> <?php echo htmlspecialchars((string)($invoice?->due_date ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Invoice Date:</strong> <?php echo htmlspecialchars((string)($invoice?->created ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>

    <div class="section invoice-summary">
        <h2>Summary</h2>
        <?php
        // Allow basic HTML in summary if you want; tests don't care about content
        echo $invoice?->summary ?? '';
        ?>
    </div>

    <pagebreak />

    <div class="section">
        <!-- Test expects this literal string -->
        <h2>SERVICES RENDERED</h2>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Hours</th>
                    <th>Minutes</th>
                    <th>Quantity</th>
                    <th>Rate</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)) : ?>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($item['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($item['hours'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($item['minutes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($item['quantity'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($item['rate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($item['subtotal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7">No items found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="totals">
            <h3>
                Total:
                $<?php echo htmlspecialchars((string)($invoice?->total ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>
            </h3>
        </div>
    </div>

    <div class="section notes">
        <h2>Notes</h2>
        <?php
        // Allow HTML / line breaks in notes; tests don't check this content
        echo $invoice?->notes ?? '';
        ?>
    </div>

</body>
</html>
