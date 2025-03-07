<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
?>
<h1>Welcome to Mothership</h1>
<p>Total Outstanding: $<?php echo number_format($this->totalOutstanding, 2); ?></p>