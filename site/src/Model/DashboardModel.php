<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Factory;

class DashboardModel extends BaseModel
{
    public function getTotalOutstanding()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('SUM(total)')
            ->from('#__mothership_invoices')
            ->where('status != 3'); // Assuming 3 = Paid
        $db->setQuery($query);

        return (float) $db->loadResult();
    }
}