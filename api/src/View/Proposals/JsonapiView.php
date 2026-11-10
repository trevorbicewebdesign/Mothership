<?php

/**
 * @package     Mothership.API
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Api\View\Proposals;

use TrevorBice\Component\Mothership\Administrator\Helper\ProposalHelper;
use TrevorBice\Component\Mothership\Api\View\ResourceView;

\defined('_JEXEC') or die;

/**
 * The proposals API view.
 */
class JsonapiView extends ResourceView
{
    /**
     * Fields rendered for a single item: columns of the row ProposalModel::getItem() loads.
     *
     * @var array
     */
    protected $fieldsToRenderItem = [
        'id',
        'number',
        'name',
        'type',
        'client_id',
        'account_id',
        'project_id',
        'rate',
        'total_low',
        'total',
        'status',
        'expires',
        'locked',
        'summary',
        'notes',
        'items',
        'created',
        'created_by',
        'modified',
        'modified_by',
    ];

    /**
     * Fields rendered for each item in a list. These are columns ProposalsModel::getListQuery()
     * selects; adding anything else here yields null.
     *
     * @var array
     */
    protected $fieldsToRenderList = [
        'id',
        'number',
        'name',
        'type',
        'client_id',
        'client_name',
        'account_id',
        'account_name',
        'project_id',
        'project_name',
        'total',
        'status',
        'locked',
        'created',
        'created_by',
    ];

    protected function statusLabel(int $status): string
    {
        return ProposalHelper::getStatus($status);
    }
}
