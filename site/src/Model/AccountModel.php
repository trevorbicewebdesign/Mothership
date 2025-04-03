<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class AccountModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('account.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load the account with status and related invoices
        $query = $db->getQuery(true)
            ->select([
                'p.*',

                // Interpreted status
                'CASE ' . $db->quoteName('p.status') .
                    ' WHEN 1 THEN ' . $db->quote('Pending') .
                    ' WHEN 2 THEN ' . $db->quote('Completed') .
                    ' WHEN 3 THEN ' . $db->quote('Failed') .
                    ' WHEN 4 THEN ' . $db->quote('Cancelled') .
                    ' WHEN 5 THEN ' . $db->quote('Refunded') .
                    ' ELSE ' . $db->quote('Unknown') .
                ' END AS status_text',

                // Related invoice info
                'inv.invoice_ids',
                'inv.invoice_numbers'
            ])
            ->from($db->quoteName('#__mothership_accounts', 'p'))

            ->join(
                'LEFT',
                '(SELECT ip.account_id,
                        GROUP_CONCAT(ip.invoice_id ORDER BY ip.invoice_id) AS invoice_ids,
                        GROUP_CONCAT(i.number ORDER BY ip.invoice_id) AS invoice_numbers
                FROM ' . $db->quoteName('#__mothership_invoice_account', 'ip') . '
                JOIN ' . $db->quoteName('#__mothership_invoices', 'i') . ' ON ip.invoice_id = i.id
                GROUP BY ip.account_id) AS inv
                ON inv.account_id = p.id'
            )

            ->where('p.id = :id')
            ->where('p.status != -1')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $account = $db->loadObject();

        return $account;
    }


    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('account.id', $id);
    }

}
