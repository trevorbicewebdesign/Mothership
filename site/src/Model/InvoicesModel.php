<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;

class InvoicesModel extends ListModel
{
    public function getItems()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__mothership_invoices')
            ->where('status != -1'); // Assuming -1 = Deleted/Archived
        $db->setQuery($query);

        return $db->loadObjectList();
    }
}