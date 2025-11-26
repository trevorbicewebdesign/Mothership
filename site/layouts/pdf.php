<?php
defined('_JEXEC') or die;

/** @var array $displayData */
$invoice = $displayData['invoice'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $invoice->number; ?></title>
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
            margin-bottom: 30px;
            border-bottom: 1px dashed #aac8e4;
            padding-bottom: 10px;
        }

        h2 {
            font-size: 16pt;
            border-bottom: 1px dashed #aac8e4;
            margin-top: 40px;
            padding-bottom: 6px;
        }

        p {
            margin: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px dashed #aac8e4;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #eaf3fa;
            font-weight: bold;
        }

        .totals {
            margin-top: 30px;
            text-align: right;
        }

        .totals h3 {
            font-size: 16pt;
            color: #2a6592;
        }

        .section {
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="invoice">
    <h1>Invoice #<?php echo $invoice->number; ?></h1>

    <div class="section">
        <p><strong>Client:</strong> <?php echo htmlspecialchars($invoice->client_name ?? ''); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($invoice->created ?? ''); ?></p>
        <p><strong>Due Date:</strong> <?php echo htmlspecialchars($invoice->due ?? ''); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($invoice->status ?? ''); ?></p>
    </div>

    <div class="section invoice-summary">
        <h2>Summary</h2>
        <?php echo $invoice->summary ?? ''; ?>
    </div>

    <pagebreak />

    <h2>Invoice Items</h2>
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
            <?php if (!empty($invoice->items)) : ?>
                <?php foreach ($invoice->items as $item) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                        <td><?php echo (float)($item['hours'] ?? 0); ?></td>
                        <td><?php echo (float)($item['minutes'] ?? 0); ?></td>
                        <td><?php echo (float)($item['quantity'] ?? 1); ?></td>
                        <td><?php echo number_format((float)($item['rate'] ?? 0), 2); ?></td>
                        <td><?php echo number_format((float)($item['subtotal'] ?? 0), 2); ?></td>
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
        <h3>Total: $<?php echo number_format((float)($invoice->total ?? 0), 2); ?></h3>
    </div>

    <div class="section invoice-notes"></div>
        <h2>Notes</h2>
        <?php echo $invoice->notes ?? ''; ?>
    </div>  

</body>
</html>

