<?php
defined('_JEXEC') or die;

use TrevorBice\Component\Mothership\Administrator\Helper\EstimateHelper;

/** @var array $displayData */
$estimate  = $displayData['estimate'];
$account  = $displayData['account'] ?? null;
$client   = $displayData['client'] ?? null;
$business = $displayData['business'] ?? null;
$items    = $estimate->items ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Estimate #<?php echo htmlspecialchars($estimate->number); ?></title>
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
    margin-bottom: 8mm;
}

.logo-company-block {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    margin-bottom: 5mm;
}

.company-info,
.client-info {
    font-size: 8pt;
    line-height: 1.5;
}

.header-right h1 {
    margin: 0 0 4mm 0;
    font-size: 20pt;
    color: #539CCD;
}

.invoice-meta p {
    margin: 1mm 0;
    font-size: 9pt;
}

.section {
    margin-top: 10mm;
}

.account-heading {
    font-size: 13pt;
    font-weight: bold;
    margin: 6mm 0 4mm;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4mm;
}

.items-table th,
.items-table td {
    padding: 4px 6px;
}

.items-table th {
    background: #FFFFFF;
    text-align: left;
    border-bottom: 1px solid #ccc;
}

.total {
    text-align: right;
    margin-top: 6mm;
    font-size: 12pt;
    font-weight: bold;
    color: #539CCD;
}

.footer {
    font-size: 9pt;
    font-weight: normal;
    margin-top: 10mm;
    line-height: 1.4;
}

.final-company-info {
    text-align: center;
    margin-top: 16mm;
    font-size: 10pt;
    color: #444;
    line-height: 1.4;
}

    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="logo-company-block" style="display: flex; flex-direction: row; align-items: flex-start;">
            <img src="/components/com_mothership/assets/images/gears.png" alt="Company Logo" style="height:120px; max-width:120px; margin-right: 10px; margin-bottom: 0;">
            <div class="company-info" style="text-align: left;">
            <?php if (!empty($business['company_name'])): ?>
                <p><?php echo nl2br(htmlspecialchars($business['company_name'])); ?></p>
            <?php endif; ?>
            <?php if (!empty($business['company_address_1'])): ?>
                <p><?php echo nl2br(htmlspecialchars($business['company_address_1'])); ?></p>
            <?php endif; ?>
            <p><?php echo nl2br(htmlspecialchars($business['company_city'])); ?>, <?php echo nl2br(htmlspecialchars($business['company_city'])); ?></p>
            <?php if (!empty($business['company_phone'])): ?>
                <p><?php echo htmlspecialchars($business['company_phone']); ?></p>
            <?php endif; ?>
            </div>
        </div>

        <div class="client-info" style="margin-top: 0;">
            <p><?php echo htmlspecialchars($client->name ?? $estimate->client_name ?? ''); ?></p>
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
        <h1>Estimate</h1>
        <div class="invoice-meta">
            <p><strong>Estimate Number: </strong> #<?php echo htmlspecialchars($estimate->number); ?></p>
            <p><strong>Estimate Status:</strong> <?php echo htmlspecialchars(EstimateHelper::getStatus($estimate->status)); ?></p>
        </div>
    </div>
</div>

<img src="/components/com_mothership/assets/images/custom-hr.jpg" alt="Separator" style="width: 100%; height: 1px; margin: 0px;" />
<div class="section account-heading">
    <?php echo htmlspecialchars($account->name ?? ''); ?>
    <?php echo $estimate->title; ?>
</div>

<img src="/components/com_mothership/assets/images/custom-hr.jpg" alt="Separator" style="width: 100%; height: 1px; margin: 0px" />

<?php echo $estimate->summary; ?>

<div class="section">
    <table class="items-table">
        <thead>
            <tr style="background-color: #f5f5f5;">
                <th style="width: 16px;"></th>
                <th>SERVICES</th>
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

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 6mm;">
    <div class="footer" style="text-align: left;">
        <strong>Have Questions? Get in touch —</strong><br>
        <?php if (!empty($business['company_email'])) : ?>
            <?php echo htmlspecialchars($business['company_email']); ?>
        <?php endif; ?>
        <?php if (!empty($business['company_email']) && !empty($business['company_phone'])) : ?>
            &nbsp;|&nbsp;
        <?php endif; ?>
        <?php if (!empty($business['company_phone'])) : ?>
            <?php echo htmlspecialchars($business['company_phone']); ?>
        <?php endif; ?>
    </div>
    <div class="total">
        AMOUNT DUE: $<?php echo number_format((float)($estimate->total ?? 0), 2); ?><br>
        <span style="font-size: 10pt; font-weight: normal; color: #777;">Due upon receipt of invoice</span>
    </div>
</div>

<div class="final-company-info">
    <?php if (!empty($business['company_name'])): ?>
        <div style="font-weight: bold;"><?php echo htmlspecialchars($business['company_name']); ?></div>
    <?php endif; ?>
    <?php if (!empty($business['company_address_1'])): ?>
        <div><?php echo htmlspecialchars($business['company_address_1']); ?></div>
    <?php endif; ?>
    <?php if (!empty($business['company_address_2'])): ?>
        <div><?php echo htmlspecialchars($business['company_address_2']); ?></div>
    <?php endif; ?>
    <?php if (!empty($business['company_city']) || !empty($business['company_state']) || !empty($business['company_zip'])): ?>
        <div>
            <?php echo htmlspecialchars($business['company_city'] ?? ''); ?>
            <?php echo !empty($business['company_state']) ? ', ' . htmlspecialchars($business['company_state']) : ''; ?>
            <?php echo htmlspecialchars($business['company_zip'] ?? ''); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($business['company_phone'])): ?>
        <div><?php echo htmlspecialchars($business['company_phone']); ?></div>
    <?php endif; ?>
</div>

</body>
</html>
