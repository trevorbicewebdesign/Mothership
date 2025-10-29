<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$client = $this->item;
?>
<h1><?php echo $client->name; ?></h1>
<hr/>
Email: <?php echo $client->email; ?><br/>
Phone: <?php echo $client->phone; ?><br/>
Address: <?php echo $client->address_1; ?><br/>
Address: <?php echo $client->address_2; ?><br/>
Location: <?php echo $client->city; ?>, <?php echo $client->state; ?> <?php echo $client->zip; ?><br/>
Default Rate: <?php echo $client->default_rate; ?><br/>
Created: <?php echo $client->created; ?><br/>
