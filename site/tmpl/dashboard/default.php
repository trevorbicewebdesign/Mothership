<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;    
use Joomla\CMS\Language\Text;
// use helper
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

$user = JFactory::getUser();
$client_id = MothershipHelper::getUserClientId();
$client_info =  MothershipHelper::getClient($client_id);
?>
<h1>Welcome, <?php echo $user->name; ?></h1>
<h2><?php echo $client_info->name; ?></h2>
<?php 
echo $client_info->address_1.'<br/>';
echo $client_info->city.', '.$client_info->state.' '.$client_info->zip.'<br/>';
?>
<br/>
<p>Total Outstanding: $<?php echo number_format($this->totalOutstanding, 2); ?></p>