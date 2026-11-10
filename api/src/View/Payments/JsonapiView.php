<?php

/**
 * @package     Mothership.API
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Api\View\Payments;

use TrevorBice\Component\Mothership\Administrator\Helper\PaymentHelper;
use TrevorBice\Component\Mothership\Api\View\ResourceView;

\defined('_JEXEC') or die;

/**
 * The payments API view.
 */
class JsonapiView extends ResourceView
{
    /**
     * Fields rendered for a single item: columns of the row PaymentModel::getItem() loads.
     *
     * @var array
     */
    protected $fieldsToRenderItem = [
        'id',
        'client_id',
        'account_id',
        'amount',
        'fee_amount',
        'fee_passed_on',
        'payment_method',
        'transaction_id',
        'payment_date',
        'processed_date',
        'status',
        'locked',
        'created_by',
        'created_at',
        'updated_at',
    ];

    /**
     * Fields rendered for each item in a list. These are columns PaymentsModel::getListQuery()
     * selects; adding anything else here yields null.
     *
     * @var array
     */
    protected $fieldsToRenderList = [
        'id',
        'client_id',
        'client_name',
        'account_id',
        'account_name',
        'amount',
        'payment_method',
        'payment_date',
        'status',
        'locked',
        'created_at',
    ];

    protected function statusLabel(int $status): string
    {
        return PaymentHelper::getStatus($status);
    }
}
