<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2008 Open Source Matters
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use TrevorBice\Component\Mothership\Administrator\Service\ExpenseReceipt;

\defined('_JEXEC') or die;

class ExpensesModel extends ListModel
{
    use HasClientAccountFilter;

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'e.id',
                'category',
                'e.category',
                'item',
                'e.item',
                'merchant',
                'e.merchant',
                'amount',
                'e.amount',
                'expense_date',
                'e.expense_date',
                'client_name',
                'c.name',
                'billable',
                'e.billable',
                'payment_account',
                'e.payment_account',
                'e.created_at',
            ];
        }
        parent::__construct($config);
    }

    protected function populateState($ordering = 'e.expense_date', $direction = 'desc')
    {
        if (empty($this->context)) {
            $this->context = $this->option . '.' . $this->getName();
        }

        // The base ListModel reads the whole filter[] array from the request and
        // sets filter.category / filter.billable state for us (same as the
        // payments list relies on for status / method).
        parent::populateState($ordering, $direction);

        // Shared, cascading Client / Account filter (see HasClientAccountFilter).
        // Runs AFTER parent so its raw filter[] read does not clobber it.
        $this->reconcileClientAccountFilterState();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id = $this->clientAccountStoreId($id);
        $id .= ':' . $this->getState('filter.category');
        $id .= ':' . $this->getState('filter.billable');
        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('e.id'),
                    $db->quoteName('e.category'),
                    $db->quoteName('e.item'),
                    $db->quoteName('e.merchant'),
                    $db->quoteName('e.client_id'),
                    $db->quoteName('e.account_id'),
                    $db->quoteName('e.project_id'),
                    $db->quoteName('c.name', 'client_name'),
                    $db->quoteName('a.name', 'account_name'),
                    $db->quoteName('pr.name', 'project_name'),
                    $db->quoteName('e.expense_date'),
                    $db->quoteName('e.amount'),
                    $db->quoteName('e.billable'),
                    $db->quoteName('e.reimbursed'),
                    $db->quoteName('e.transaction_id'),
                    $db->quoteName('e.payment_account'),
                    $db->quoteName('e.created_at'),
                ]
            )
        );

        $query->from($db->quoteName('#__mothership_expenses', 'e'))
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON ' . $db->quoteName('e.client_id') . ' = ' . $db->quoteName('c.id'))
            ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON ' . $db->quoteName('e.account_id') . ' = ' . $db->quoteName('a.id'))
            ->join('LEFT', $db->quoteName('#__mothership_projects', 'pr') . ' ON ' . $db->quoteName('e.project_id') . ' = ' . $db->quoteName('pr.id'));

        // Filter by search term (item / merchant, or id:N).
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'id:') === 0) {
                $sid = (int) substr($search, 3);
                $query->where($db->quoteName('e.id') . ' = :search')
                    ->bind(':search', $sid, ParameterType::INTEGER);
            } else {
                $like = '%' . str_replace(' ', '%', $search) . '%';
                $query->where('(' . $db->quoteName('e.item') . ' LIKE :search1 OR ' . $db->quoteName('e.merchant') . ' LIKE :search2)')
                    ->bind(':search1', $like)
                    ->bind(':search2', $like);
            }
        }

        // Filter by client / account (shared, cascading).
        $this->applyClientAccountFilterQuery($query, 'e');

        // Filter by category.
        if (($category = $this->getState('filter.category', '')) !== '' && $category !== null) {
            $query->where($db->quoteName('e.category') . ' = :fcategory')
                ->bind(':fcategory', $category);
        }

        // Filter by billable.
        if (($billable = $this->getState('filter.billable', '')) !== '' && $billable !== null) {
            $b = (int) $billable;
            $query->where($db->quoteName('e.billable') . ' = :fbillable')
                ->bind(':fbillable', $b, ParameterType::INTEGER);
        }

        // Ordering
        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'e.expense_date'))) . ' ' .
            $db->escape($this->getState('list.direction', 'DESC'))
        );

        return $query;
    }

    public function checkin($ids = null)
    {
        if (empty($ids)) {
            return false;
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $ids = array_map('intval', $ids);
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_expenses'))
            ->set($db->quoteName('checked_out') . ' = NULL')
            ->set($db->quoteName('checked_out_time') . ' = NULL')
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
        $db->setQuery($query);
        try {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Delete the specified expenses.
     *
     * @param array|int $ids  Expense IDs to delete.
     * @return bool  True on success.
     */
    public function delete($ids = [])
    {
        if (empty($ids)) {
            return false;
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $ids = array_map('intval', $ids);
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__mothership_expenses'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
        $db->setQuery($query);

        try {
            $db->execute();

            foreach ($ids as $id) {
                ExpenseReceipt::deleteFor($id);
            }

            return true;
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
}
