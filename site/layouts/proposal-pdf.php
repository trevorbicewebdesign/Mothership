<?php

use TrevorBice\Component\Mothership\Administrator\Helper\ProposalHelper;

defined('_JEXEC') or die;

/** @var array $displayData */
$proposal = $displayData['proposal'];
$account  = $displayData['account'] ?? null;
$client   = $displayData['client'] ?? null;
$business = $displayData['business'] ?? null;
// Items fallback to array
$items = $proposal?->items ?? [];
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
            text-align: left;
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
    <h1>Proposal of Services</h1>
    <h2>Proposal Number: #<?php echo $proposal->number; ?></h2>


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

            <div class="proposal-meta">
                <!-- Test expects these exact labels -->
                <p><strong>Proposal Status:</strong><?php echo htmlspecialchars(ProposalHelper::getStatus($proposal->status)); ?></p>
                <p><strong>Proposal Date:</strong> <?php echo htmlspecialchars((string)($proposal?->created ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>

    <div class="section proposal-summary">
        <h2>Summary</h2>
        <?php echo $proposal->summary ?? ''; ?>
    </div>

    <pagebreak />

    <h2>ITEMS</h2>
    <table>
        <thead>
            <tr>
                <th>Name / Description</th>
                <th>Range</th>
                <th>Rate</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($proposal->items)) : ?>
                <?php foreach ($proposal->items as $item) : ?>
                    <tr>
                        <?php if($item['type']==='hourly'): ?>
                        <td><?php echo htmlspecialchars($item['name'] ?? ''); ?><br/><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                        <td><?php echo ($item['time_low'] ?? 0); ?> - <?php echo ($item['time'] ?? 0); ?></td>
                        <td><?php echo number_format((float)($item['rate'] ?? 0), 2); ?></td>
                        <td><?php echo number_format((float)($item['subtotal_low'] ?? 0), 2); ?> - <?php echo number_format((float)($item['subtotal'] ?? 0), 2); ?></td>
                        <?php else: ?>
                        <td><?php echo htmlspecialchars($item['name'] ?? ''); ?><br/><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                        <td>N/A</td>
                        <td><?php echo number_format((float)($item['rate'] ?? 0), 2); ?></td>
                        <td><?php echo number_format((float)($item['subtotal'] ?? 0), 2); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4">No items found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="totals">
        <h3>$<?php echo number_format((float)($proposal->total_low ?? 0), 2); ?> - $<?php echo number_format((float)($proposal->total ?? 0), 2); ?> </h3>
    </div>

    <div class="section proposal-notes">
        <h2>Notes</h2>
        <?php echo $proposal->notes ?? ''; ?>
    </div>  

</body>
</html>

