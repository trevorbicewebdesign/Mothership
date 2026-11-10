<?php

/**
 * @package     Mothership.API
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Api\View\Invoices;

use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;
use TrevorBice\Component\Mothership\Api\View\ResourceView;

\defined('_JEXEC') or die;

/**
 * The invoices API view.
 */
class JsonapiView extends ResourceView
{
    /**
     * Fields rendered for a single item: columns of the row InvoiceModel::getItem() loads.
     *
     * @var array
     */
    protected $fieldsToRenderItem = [
        'id',
        'number',
        'title',
        'client_id',
        'account_id',
        'project_id',
        'rate',
        'total',
        'status',
        'due_date',
        'sent_date',
        'paid_date',
        'locked',
        'summary',
        'notes',
        'items',
        'created',
        'created_by',
    ];

    /**
     * Fields rendered for each item in a list. These are columns InvoicesModel::getListQuery()
     * selects; adding anything else here yields null.
     *
     * @var array
     */
    protected $fieldsToRenderList = [
        'id',
        'number',
        'title',
        'client_id',
        'client_name',
        'account_id',
        'account_name',
        'project_id',
        'project_name',
        'total',
        'total_paid',
        'status',
        'payment_status',
        'locked',
        'created',
        'created_by',
    ];

    protected function statusLabel(int $status): string
    {
        return InvoiceHelper::getStatus($status);
    }
}
