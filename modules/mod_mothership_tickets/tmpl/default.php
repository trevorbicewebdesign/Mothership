<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_tickets
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array   $tickets    Latest tickets.
 * @var  int     $openCount  Count of open tickets.
 * @var  object  $params     Module params.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// status => [chip bg, chip text, dot colour, label]
$statusMeta = [
    'new'         => ['#e8f0fe', '#1a56db', '#2563eb', 'New'],
    'open'        => ['#e8f0fe', '#1a56db', '#2563eb', 'Open'],
    'in_progress' => ['#fff4e5', '#b45309', '#d97706', 'In Progress'],
    'waiting'     => ['#eef0f2', '#5a626c', '#9aa0a6', 'Waiting'],
    'resolved'    => ['#e6f4ea', '#137333', '#16a34a', 'Resolved'],
    'closed'      => ['#eef0f2', '#5a626c', '#9aa0a6', 'Closed'],
];
$listUrl = Route::_('index.php?option=com_mothership&view=tickets');
$newUrl  = Route::_('index.php?option=com_mothership&view=tickets&layout=create');
?>
<div class="msmodtix">
    <?php if (empty($tickets)) : ?>
        <p class="msmodtix__empty"><?php echo Text::_('MOD_MOTHERSHIP_TICKETS_NONE'); ?></p>
    <?php else : ?>
        <div class="msmodtix__head">
            <span class="msmodtix__count"><?php echo Text::sprintf('MOD_MOTHERSHIP_TICKETS_OPEN_COUNT', (int) $openCount); ?></span>
        </div>
        <ul class="msmodtix__list">
            <?php foreach ($tickets as $t) :
                $m   = $statusMeta[(string) $t->status] ?? ['#eef0f2', '#5a626c', '#9aa0a6', ucfirst(str_replace('_', ' ', (string) $t->status))];
                $upd = $t->modified ?: $t->created;
                $url = Route::_('index.php?option=com_mothership&task=ticket.edit&id=' . (int) $t->id);
                ?>
                <li class="msmodtix__item">
                    <span class="msmodtix__dot" style="background:<?php echo $m[2]; ?>;"></span>
                    <div class="msmodtix__body">
                        <a class="msmodtix__subj" href="<?php echo $url; ?>"><?php echo htmlspecialchars((string) $t->subject); ?></a>
                        <div class="msmodtix__meta">
                            <?php echo htmlspecialchars((string) ($t->client_name ?? '—')); ?>
                            <?php if ($upd) : ?><span class="msmodtix__sep">&middot;</span><?php echo date('M j, Y', strtotime((string) $upd)); ?><?php endif; ?>
                        </div>
                    </div>
                    <span class="msmodtix__chip" style="background:<?php echo $m[0]; ?>;color:<?php echo $m[1]; ?>;"><?php echo htmlspecialchars($m[3]); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="msmodtix__foot">
        <a href="<?php echo $newUrl; ?>"><?php echo Text::_('MOD_MOTHERSHIP_TICKETS_NEW'); ?></a>
        <a href="<?php echo $listUrl; ?>"><?php echo Text::_('MOD_MOTHERSHIP_TICKETS_VIEW_ALL'); ?></a>
    </div>
</div>
<style>
.msmodtix { font-size: 14px; color: #1f2733; padding: 4px 14px 14px; }
.msmodtix__empty { color: #6b7280; margin: 6px 0; }
.msmodtix__head { margin-bottom: 8px; }
.msmodtix__count { font-size: 12px; font-weight: 600; color: #b45309; background: #fff4e5; border-radius: 100px; padding: 2px 10px; }
.msmodtix__list { list-style: none; margin: 0; padding: 0; }
.msmodtix__item { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-top: 1px solid #f1f2f5; }
.msmodtix__item:first-child { border-top: 0; }
.msmodtix__dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.msmodtix__body { flex: 1; min-width: 0; }
.msmodtix__subj { display: block; font-weight: 600; color: #1f2733; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.msmodtix__subj:hover { color: #2C5282; text-decoration: underline; }
.msmodtix__meta { color: #8a929c; font-size: 12px; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.msmodtix__sep { margin: 0 5px; }
.msmodtix__chip { flex-shrink: 0; display: inline-block; padding: 2px 9px; border-radius: 100px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
.msmodtix__foot { display: flex; justify-content: space-between; gap: 10px; margin-top: 12px; padding-top: 10px; border-top: 1px solid #eef0f4; font-size: 13px; }
.msmodtix__foot a { text-decoration: none; color: #2C5282; font-weight: 500; }
</style>
