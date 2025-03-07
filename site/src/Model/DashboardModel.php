<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Factory;

class DashboardModel extends BaseModel
{
    public function getTotalOutstanding(): float
    {
        $clientId = MothershipHelper::getUserClientId();

        if (!$clientId) {
            return 0.0; // No client = no outstanding invoices
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('SUM(total)')
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('status') . ' != 3')
            ->where($db->quoteName('client_id') . ' = ' . (int) $clientId);

        $db->setQuery($query);

        return (float) ($db->loadResult() ?? 0);
    }
}
