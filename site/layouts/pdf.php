<?php
defined('_JEXEC') or die;

/** @var array $displayData */
$invoice  = $displayData['invoice'];
$account  = $displayData['account'] ?? null;
$client   = $displayData['client'] ?? null;
$business = $displayData['business'] ?? null;
$items    = $invoice->items ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo htmlspecialchars($invoice->number); ?></title>
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            font-size: 10pt;
            color: #3A3A3A;
            margin: 20mm;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10mm;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            width: 55%;
        }

        .logo-company-block {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 6mm;
        }

        .company-info,
        .client-info {
            font-size: 8pt;
            line-height: 1.4;
        }

        .header-right {
            width: 40%;
            text-align: right;
        }

        .header-right h1 {
            margin: 0 0 6mm 0;
            font-size: 20pt;
            color: #539CCD;
        }

        .invoice-meta p {
            margin: 0;
            font-size: 9pt;
        }

        .section {
            margin-top: 12mm;
        }

        .account-heading {
            font-size: 14pt;
            font-weight: bold;
            margin: 6mm 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5mm;
        }

        .items-table th,
        .items-table td {
            padding: 6px 8px;
        }

        .items-table th {
            background: #FFFFFF;
            text-align: left;
            border-bottom: 1px solid #ccc;
        }

        .total {
            text-align: right;
            margin-top: 8mm;
            font-size: 12pt;
            font-weight: bold;
            color: #539CCD;
        }

        .footer {
            margin-top: 15mm;
            font-size: 8pt;
            text-align: center;
            color: #999;
        }

        hr {
            border: none;
            border-top: 1px solid #ccc;
            margin: 8mm 0 4mm;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="logo-company-block" style="display: flex; flex-direction: column; align-items: flex-start;">
            <img src="https://via.placeholder.com/120x48?text=Logo" alt="Company Logo" style="height:48px; max-width:120px; margin-bottom: 4mm;">
            <div class="company-info">
                <?php if (!empty($business['company_name'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($business['company_name'])); ?></p>
                <?php endif; ?>
                <?php if (!empty($business['company_address_1'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($business['company_address_1'])); ?></p>
                <?php endif; ?>
                <?php if (!empty($business['company_phone'])): ?>
                    <p><?php echo htmlspecialchars($business['company_phone']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="client-info" style="margin-top: 6mm;">
            <p><?php echo htmlspecialchars($client->name ?? $invoice->client_name ?? ''); ?></p>
            <?php if (!empty($client->address_1)): ?>
                <p><?php echo htmlspecialchars($client->address_1); ?></p>
            <?php endif; ?>
            <?php if (!empty($client->address_2)): ?>
                <p><?php echo htmlspecialchars($client->address_2); ?></p>
            <?php endif; ?>
            <?php if (!empty($client->city) || !empty($client->state) || !empty($client->zip)): ?>
                <p>
                    <?php echo htmlspecialchars($client->city ?? ''); ?>
                    <?php echo !empty($client->state) ? ', ' . htmlspecialchars($client->state) : ''; ?>
                    <?php echo htmlspecialchars($client->zip ?? ''); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="header-right">
        <h1>Invoice of Services</h1>
        <div class="invoice-meta">
            <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoice->number); ?></p>
            <p><strong>Invoice Status:</strong> <?php echo htmlspecialchars($invoice->status); ?></p>
            <p><strong>Invoice Due:</strong> <?php echo htmlspecialchars($invoice->due_date ?? '—'); ?></p>
        </div>
    </div>
</div>

<img src="/components/com_mothership/assets/images/custom-hr.jpg" alt="Separator" style="width: 100%; height: 1px; margin: 0px;" />
<div class="section account-heading">
    <?php echo htmlspecialchars($account->name ?? ''); ?>
</div>
<img src="/components/com_mothership/assets/images/custom-hr.jpg" alt="Separator" style="width: 100%; height: 1px; margin: 0px" />

<div class="section">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 16px;"></th>
                <th>SERVICES RENDERED</th>
                <th style="width: 50px;">Hours</th>
                <th style="width: 50px;">Rate</th>
                <th style="width: 70px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 0; ?>
        <?php foreach ($items as $item): ?>
            <?php
                $rowStyle = ($i % 2 === 0)
                    ? 'background-color: #ffffff;'
                    : 'background-color: #f5f5f5;';
                $i++;
            ?>
            <tr style="<?php echo $rowStyle; ?>">
                <td><img src="/components/com_mothership/assets/images/invoice-bullet.png" alt="•" style="width:16px;height:16px;" /></td>
                <td><?php echo htmlspecialchars($item['name'] ?? ''); ?></td>
                <td style="text-align: right;"><?php echo number_format((float)($item['quantity'] ?? 0), 2); ?></td>
                <td style="text-align: right;">$<?php echo number_format((float)($item['rate'] ?? 0), 2); ?></td>
                <td style="text-align: right;">$<?php echo number_format((float)($item['subtotal'] ?? 0), 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="total">
    AMOUNT DUE: $<?php echo number_format((float)($invoice->total ?? 0), 2); ?><br>
    <span style="font-size: 10pt; font-weight: normal; color: #777;">Due upon receipt of invoice</span>
</div>

<div class="footer">
    Have Questions? Get in touch —
    <?php echo htmlspecialchars($business['email'] ?? 'trevorbicewebdesign@gmail.com'); ?> |
    <?php echo htmlspecialchars($business['phone'] ?? '707-880-0156'); ?>
</div>

</body>
</html>
