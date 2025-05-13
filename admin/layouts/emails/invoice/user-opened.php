<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;

$fname = $displayData['fname'];
?>
<p>Hello <?= $fname; ?>,</p>
<p>You have a new invoice.</p>
