<?php
defined('_JEXEC') or die;


/** @var object $invoice */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $invoice->number; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            margin: 20px;
        }
        h1, h2, h3 {
            margin: 0;
            padding: 0;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .totals {
            margin-top: 30px;
            text-align: right;
        }
    </style>
</head>
<body>

    <h1>Invoice #<?php echo $invoice->number; ?></h1>

    <p><strong>Client:</strong> <?php echo htmlspecialchars($invoice->client_name ?? ''); ?></p>
    <p><strong>Date:</strong> <?php echo htmlspecialchars($invoice->created ?? ''); ?></p>
    <p><strong>Due Date:</strong> <?php echo htmlspecialchars($invoice->due ?? ''); ?></p>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($invoice->status ?? ''); ?></p>

    <h2>Invoice Items</h2>
    <table>
        <thead>
            <tr>
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
                    <td colspan="6">No items found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="totals">
        <h3>Total: $<?php echo number_format((float)($invoice->total ?? 0), 2); ?></h3>
    </div>

</body>
</html>
