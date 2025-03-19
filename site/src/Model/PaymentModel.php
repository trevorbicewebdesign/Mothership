<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class PaymentModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('payment.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load the payment
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__mothership_payments')
            ->where('id = ' . (int) $id)
            ->where('status != -1');
        $db->setQuery($query);
        $payment = $db->loadObject();

        return $payment;
    }

    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('payment.id', $id);
    }

}
