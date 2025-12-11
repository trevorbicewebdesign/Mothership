<?php
defined('_JEXEC') or die;

/** @var array $displayData */
$proposal = $displayData['proposal'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Proposal #<?php echo $proposal->number; ?></title>
    <style>
        body.proposal {
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
<body class="proposal">
    <h1>Proposal #<?php echo $proposal->number; ?></h1>

    <div class="section">
        <p><strong>Client:</strong> <?php echo htmlspecialchars($proposal->client_name ?? ''); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($proposal->created ?? ''); ?></p>
        <p><strong>Due Date:</strong> <?php echo htmlspecialchars($proposal->due ?? ''); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($proposal->status ?? ''); ?></p>
    </div>

    <div class="section proposal-summary">
        <h2>Summary</h2>
        <?php echo $proposal->summary ?? ''; ?>
    </div>

    <pagebreak />

    <h2>Proposal Items</h2>
    <table>
        <thead>
            <tr>
                <th>Name / Description</th>
                <th>Range</th>
                <th>Rate</th>
                <th>Subtotal Low</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($proposal->items)) : ?>
                <?php foreach ($proposal->items as $item) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name'] ?? ''); ?><br/><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                        <td><?php echo ($item['time_low'] ?? 0); ?> - <?php echo ($item['time'] ?? 0); ?></td>
                        <td><?php echo number_format((float)($item['rate'] ?? 0), 2); ?></td>
                        <td><?php echo number_format((float)($item['subtotal_low'] ?? 0), 2); ?></td>
                        <td><?php echo number_format((float)($item['subtotal'] ?? 0), 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                 <?php foreach ($proposal->items as $item) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name'] ?? ''); ?><br/><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                        <td><?php echo ($item['time_low'] ?? 0); ?> - <?php echo ($item['time'] ?? 0); ?></td>
                        <td><?php echo number_format((float)($item['rate'] ?? 0), 2); ?></td>
                        <td><?php echo number_format((float)($item['subtotal_low'] ?? 0), 2); ?></td>
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
        <h3>Total: $<?php echo number_format((float)($proposal->total ?? 0), 2); ?></h3>
    </div>

    <div class="section proposal-notes"></div>
        <h2>Notes</h2>
        <?php echo $proposal->notes ?? ''; ?>
    </div>  

</body>
</html>

