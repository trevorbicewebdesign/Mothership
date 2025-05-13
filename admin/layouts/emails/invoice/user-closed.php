<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;

$fname = $displayData['fname'];
?>
<p>Hello <?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>,</p>
<p>Your invoice has been marked as closed.</p>
