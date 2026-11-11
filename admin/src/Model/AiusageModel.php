<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiUsage;

\defined('_JEXEC') or die;

/**
 * The AI usage log: one row per provider call, newest first. Read only; rows are written by
 * AiUsage::record().
 */
class AiusageModel extends ListModel
{
    use HasClientAccountFilter;

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'u.id',
                'u.created',
                'feature',
                'u.feature',
                'u.model',
                'u.input_tokens',
                'u.output_tokens',
                'u.cost',
                'c.name',
            ];
        }
        parent::__construct($config);
    }

    protected function populateState($ordering = 'u.created', $direction = 'desc')
    {
        if (empty($this->context)) {
            $this->context = $this->option . '.' . $this->getName();
        }

        parent::populateState($ordering, $direction);

        // Shared, cascading Client / Account filter (see HasClientAccountFilter).
        // Runs AFTER parent so its raw filter[] read does not clobber it.
        $this->reconcileClientAccountFilterState();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id = $this->clientAccountStoreId($id);
        $id .= ':' . $this->getState('filter.feature');
        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('u.id'),
                    $db->quoteName('u.provider'),
                    $db->quoteName('u.model'),
                    $db->quoteName('u.feature'),
                    $db->quoteName('u.resource_type'),
                    $db->quoteName('u.resource_id'),
                    $db->quoteName('u.client_id'),
                    $db->quoteName('c.name', 'client_name'),
                    $db->quoteName('u.input_tokens'),
                    $db->quoteName('u.output_tokens'),
                    $db->quoteName('u.cost'),
                    $db->quoteName('u.created'),
                ]
            )
        );

        $query->from($db->quoteName(AiUsage::TABLE, 'u'))
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON ' . $db->quoteName('u.client_id') . ' = ' . $db->quoteName('c.id'));

        // Filter by search term (the model's name).
        if ($search = trim($this->getState('filter.search', ''))) {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where($db->quoteName('u.model') . ' LIKE :search')
                ->bind(':search', $like);
        }

        // Filter by client / account (shared, cascading).
        $this->applyClientAccountFilterQuery($query, 'u');

        // Filter by feature.
        if (($feature = $this->getState('filter.feature', '')) !== '' && $feature !== null) {
            $query->where($db->quoteName('u.feature') . ' = :ffeature')
                ->bind(':ffeature', $feature);
        }

        // Ordering
        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'u.created'))) . ' ' .
            $db->escape($this->getState('list.direction', 'DESC'))
        );

        return $query;
    }

    /**
     * This month, last month and all time.
     */
    public function getSummary(): array
    {
        return AiUsage::summary();
    }
}
