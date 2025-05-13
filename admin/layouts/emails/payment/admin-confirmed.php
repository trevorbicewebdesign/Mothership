<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;

$admin_fname = $displayData['admin_fname'];
?>
<p>Hello <?= $admin_fname; ?>,</p>
<p>A payment has been confirmed.</p>