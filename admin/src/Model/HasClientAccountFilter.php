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
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Cascading Client / Account list filter shared by the invoices, projects,
 * domains and payments list models. All four source tables expose client_id
 * and account_id columns, so the behaviour is identical bar the table alias.
 *
 * The selection is a SHARED scope: picking a client / account on any one of
 * these lists carries across to the others as you navigate between them. The
 * shared value is mirrored into each view's own filter context so the filter
 * form renders the current selection.
 */
trait HasClientAccountFilter
{
    /** Shared (cross-list) user-state keys. */
    protected $sharedClientKey  = 'com_mothership.filter.client_id';
    protected $sharedAccountKey = 'com_mothership.filter.account_id';

    /**
     * Reconcile the client_id / account_id filters against each other and
     * persist them to the shared scope, this view's filter context and model
     * state.
     *
     * MUST be called AFTER parent::populateState() (so the base class's raw
     * filter[] read does not clobber the reconciliation), and it deliberately
     * reads from the request / user state directly. NEVER read these via
     * $this->getState() inside populateState() - that re-enters populateState()
     * and recurses (memory / session blowup).
     */
    protected function reconcileClientAccountFilterState(): void
    {
        $app = Factory::getApplication();

        $requestFilter = (array) $app->getInput()->get('filter', [], 'array');

        // A value submitted on this list wins; otherwise inherit the shared scope.
        $clientId = \array_key_exists('client_id', $requestFilter)
            ? (string) $requestFilter['client_id']
            : (string) $app->getUserState($this->sharedClientKey, '');
        $accountId = \array_key_exists('account_id', $requestFilter)
            ? (string) $requestFilter['account_id']
            : (string) $app->getUserState($this->sharedAccountKey, '');

        // Reconcile so each adjusts the other:
        //  - account picked with no client selected -> adopt the account's client;
        //  - client changed to one the account doesn't belong to -> drop the account.
        if ($accountId !== '') {
            $db = $this->getDatabase();
            $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName('client_id'))
                    ->from($db->quoteName('#__mothership_accounts'))
                    ->where($db->quoteName('id') . ' = ' . (int) $accountId)
            );
            $accClient = (string) ($db->loadResult() ?? '');

            if ($accClient !== '') {
                if ($clientId === '') {
                    $clientId = $accClient;
                } elseif ($clientId !== $accClient) {
                    $accountId = '';
                }
            }
        }

        // Shared scope (read by every list and by the filter fields).
        $app->setUserState($this->sharedClientKey, $clientId);
        $app->setUserState($this->sharedAccountKey, $accountId);

        // This view's filter context, so the filter form shows the selection.
        $app->setUserState("{$this->context}.filter.client_id", $clientId);
        $app->setUserState("{$this->context}.filter.account_id", $accountId);

        // Model state, so getListQuery() filters on it.
        $this->setState('filter.client_id', $clientId);
        $this->setState('filter.account_id', $accountId);
    }

    /**
     * Append the client / account filters to the list cache store id.
     */
    protected function clientAccountStoreId(string $id): string
    {
        $id .= ':' . $this->getState('filter.client_id');
        $id .= ':' . $this->getState('filter.account_id');

        return $id;
    }

    /**
     * Add the client / account WHERE clauses to the list query.
     *
     * @param DatabaseQuery $query The query being built.
     * @param string        $alias The source table alias (e.g. 'i', 'p', 'd').
     */
    protected function applyClientAccountFilterQuery($query, $alias): void
    {
        $db = $this->getDatabase();

        if (($clientId = $this->getState('filter.client_id', '')) !== '' && $clientId !== null) {
            $cid = (int) $clientId;
            $query->where($db->quoteName($alias . '.client_id') . ' = :fclient')
                ->bind(':fclient', $cid, ParameterType::INTEGER);
        }

        if (($accountId = $this->getState('filter.account_id', '')) !== '' && $accountId !== null) {
            $aid = (int) $accountId;
            $query->where($db->quoteName($alias . '.account_id') . ' = :faccount')
                ->bind(':faccount', $aid, ParameterType::INTEGER);
        }
    }
}
