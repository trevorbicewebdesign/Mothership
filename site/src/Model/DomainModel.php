<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DomainModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('domain.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load the domain with status and related invoices
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
            ->from($db->quoteName('#__mothership_domains', 'p'))

            ->join(
                'LEFT',
                '(SELECT ip.domain_id,
                        GROUP_CONCAT(ip.invoice_id ORDER BY ip.invoice_id) AS invoice_ids,
                        GROUP_CONCAT(i.number ORDER BY ip.invoice_id) AS invoice_numbers
                FROM ' . $db->quoteName('#__mothership_invoice_domain', 'ip') . '
                JOIN ' . $db->quoteName('#__mothership_invoices', 'i') . ' ON ip.invoice_id = i.id
                GROUP BY ip.domain_id) AS inv
                ON inv.domain_id = p.id'
            )

            ->where('p.id = :id')
            ->where('p.status != -1')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $domain = $db->loadObject();

        return $domain;
    }


    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('domain.id', $id);
    }

}
