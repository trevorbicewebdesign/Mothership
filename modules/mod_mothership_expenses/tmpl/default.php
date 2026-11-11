<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_expenses
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array   $monthly      Month (1-12) => expense total for $year.
 * @var  int     $year         The year the chart covers.
 * @var  float   $totalToDate  All-time expense total.
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
$expensesUrl = Route::_('index.php?option=com_mothership&view=expenses');
?>
<div class="msmodexp">
    <div class="msmodexp__head">
        <span class="msmodexp__title"><?php echo (int) $year; ?> <span class="msmodexp__title-sub"><?php echo Text::_('MOD_MOTHERSHIP_EXPENSES_YTD'); ?></span></span>
        <span class="msmodexp__ytd"><?php echo $money($ytd); ?></span>
    </div>

    <?php if (!$hasData) : ?>
        <p class="msmodexp__empty"><?php echo Text::_('MOD_MOTHERSHIP_EXPENSES_NONE'); ?></p>
    <?php else : ?>
        <div class="msmodexp__chart" role="img" aria-label="<?php echo Text::sprintf('MOD_MOTHERSHIP_EXPENSES_HEADING', $year); ?>">
            <?php for ($m = 1; $m <= 12; $m++) :
                $v = (float) $monthly[$m];
                $h = $max > 0 ? max(($v > 0 ? 3 : 0), (int) round($v / $max * 100)) : 0;
                ?>
                <div class="msmodexp__col">
                    <div class="msmodexp__track">
                        <div class="msmodexp__val" style="bottom:<?php echo $h; ?>%;"><?php echo $money($v); ?></div>
                        <div class="msmodexp__bar<?php echo $m > $curMonth ? ' is-future' : ''; ?>" style="height:<?php echo $h; ?>%;"></div>
                    </div>
                    <div class="msmodexp__lbl" title="<?php echo htmlspecialchars($labels[$m] . ' ' . $year, ENT_QUOTES, 'UTF-8'); ?>"><?php echo substr($labels[$m], 0, 1); ?></div>
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <?php if ($showTotal) : ?>
        <a class="msmodexp__total" href="<?php echo $expensesUrl; ?>">
            <span class="msmodexp__total-l"><?php echo Text::_('MOD_MOTHERSHIP_EXPENSES_TOTAL_TO_DATE'); ?></span>
            <span class="msmodexp__total-v"><?php echo $money($totalToDate); ?></span>
        </a>
    <?php endif; ?>
</div>
<style>
.msmodexp { font-size: 14px; color: #1f2733; padding: 4px 14px 14px; }
.msmodexp__head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
.msmodexp__title { font-weight: 600; }
.msmodexp__title-sub { color: #8a929c; font-weight: 400; font-size: 13px; }
.msmodexp__ytd { font-weight: 700; font-variant-numeric: tabular-nums; color: #C2410C; }
.msmodexp__empty { color: #6b7280; margin: 6px 0; }
.msmodexp__chart { display: flex; align-items: flex-end; gap: 6px; height: 150px; padding: 20px 0 4px; border-bottom: 1px solid #e6e8ee; }
.msmodexp__col { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; align-items: center; height: 100%; }
.msmodexp__track { position: relative; flex: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center; }
.msmodexp__val { position: absolute; left: 50%; transform: translate(-50%, -4px); background: #1f2937; color: #fff; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity .12s; z-index: 3; }
.msmodexp__col:hover .msmodexp__val { opacity: 1; }
.msmodexp__bar { width: 70%; max-width: 26px; min-height: 0; background: #C2410C; border-radius: 3px 3px 0 0; transition: opacity .15s; }
.msmodexp__col:hover .msmodexp__bar { opacity: .85; }
.msmodexp__bar.is-future { background: #e2d5c7; }
.msmodexp__lbl { margin-top: 6px; font-size: 11px; color: #8a929c; text-transform: uppercase; letter-spacing: .02em; }
.msmodexp__total { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #eef0f4; text-decoration: none; color: inherit; }
.msmodexp__total:hover { color: #C2410C; }
.msmodexp__total:hover .msmodexp__total-v { color: #C2410C; }
.msmodexp__total-l { color: #6b7280; font-size: 13px; }
.msmodexp__total-v { font-size: 22px; font-weight: 700; font-variant-numeric: tabular-nums; letter-spacing: -.01em; }
</style>
