<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_invoiced
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array   $monthly      Month (1-12) => invoiced total for $year.
 * @var  int     $year         The year the chart covers.
 * @var  float   $totalToDate  All-time invoiced total.
 * @var  object  $params       Module params.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$showTotal = (int) $params->get('show_total', 1);
$ytd       = array_sum($monthly);
$max       = max($monthly) ?: 0.0;
$curMonth  = (int) date('n');
$hasData   = $ytd > 0 || $totalToDate > 0;

$labels = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$money  = static fn ($n) => '$' . number_format((float) $n, 2);
$invoicesUrl = Route::_('index.php?option=com_mothership&view=invoices');
?>
<div class="msmodinv">
    <div class="msmodinv__head">
        <span class="msmodinv__title"><?php echo (int) $year; ?> <span class="msmodinv__title-sub"><?php echo Text::_('MOD_MOTHERSHIP_INVOICED_YTD'); ?></span></span>
        <span class="msmodinv__ytd"><?php echo $money($ytd); ?></span>
    </div>

    <?php if (!$hasData) : ?>
        <p class="msmodinv__empty"><?php echo Text::_('MOD_MOTHERSHIP_INVOICED_NONE'); ?></p>
    <?php else : ?>
        <div class="msmodinv__chart" role="img" aria-label="<?php echo Text::sprintf('MOD_MOTHERSHIP_INVOICED_HEADING', $year); ?>">
            <?php for ($m = 1; $m <= 12; $m++) :
                $v = (float) $monthly[$m];
                $h = $max > 0 ? max(($v > 0 ? 3 : 0), (int) round($v / $max * 100)) : 0;
                ?>
                <div class="msmodinv__col">
                    <div class="msmodinv__track">
                        <div class="msmodinv__val" style="bottom:<?php echo $h; ?>%;"><?php echo $money($v); ?></div>
                        <div class="msmodinv__bar<?php echo $m > $curMonth ? ' is-future' : ''; ?>" style="height:<?php echo $h; ?>%;"></div>
                    </div>
                    <div class="msmodinv__lbl" title="<?php echo htmlspecialchars($labels[$m] . ' ' . $year, ENT_QUOTES, 'UTF-8'); ?>"><?php echo substr($labels[$m], 0, 1); ?></div>
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <?php if ($showTotal) : ?>
        <a class="msmodinv__total" href="<?php echo $invoicesUrl; ?>">
            <span class="msmodinv__total-l"><?php echo Text::_('MOD_MOTHERSHIP_INVOICED_TOTAL_TO_DATE'); ?></span>
            <span class="msmodinv__total-v"><?php echo $money($totalToDate); ?></span>
        </a>
    <?php endif; ?>
</div>
<style>
.msmodinv { font-size: 14px; color: #1f2733; padding: 4px 14px 14px; }
.msmodinv__head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
.msmodinv__title { font-weight: 600; }
.msmodinv__title-sub { color: #8a929c; font-weight: 400; font-size: 13px; }
.msmodinv__ytd { font-weight: 700; font-variant-numeric: tabular-nums; color: #2C5282; }
.msmodinv__empty { color: #6b7280; margin: 6px 0; }
.msmodinv__chart { display: flex; align-items: flex-end; gap: 6px; height: 150px; padding: 20px 0 4px; border-bottom: 1px solid #e6e8ee; }
.msmodinv__col { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; align-items: center; height: 100%; }
.msmodinv__track { position: relative; flex: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center; }
.msmodinv__val { position: absolute; left: 50%; transform: translate(-50%, -4px); background: #1f2937; color: #fff; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity .12s; z-index: 3; }
.msmodinv__col:hover .msmodinv__val { opacity: 1; }
.msmodinv__bar { width: 70%; max-width: 26px; min-height: 0; background: #2C5282; border-radius: 3px 3px 0 0; transition: opacity .15s; }
.msmodinv__col:hover .msmodinv__bar { opacity: .85; }
.msmodinv__bar.is-future { background: #cbd5e1; }
.msmodinv__lbl { margin-top: 6px; font-size: 11px; color: #8a929c; text-transform: uppercase; letter-spacing: .02em; }
.msmodinv__total { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #eef0f4; text-decoration: none; color: inherit; }
.msmodinv__total:hover { color: #2C5282; }
.msmodinv__total:hover .msmodinv__total-v { color: #2C5282; }
.msmodinv__total-l { color: #6b7280; font-size: 13px; }
.msmodinv__total-v { font-size: 22px; font-weight: 700; font-variant-numeric: tabular-nums; letter-spacing: -.01em; }
</style>
