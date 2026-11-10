<?php

/**
 * @package     Mothership.API
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Api\Controller;

\defined('_JEXEC') or die;

/**
 * Payments API controller. filter[client_id], filter[account_id], filter[status] (the numeric status), filter[payment_method].
 */
class PaymentsController extends ResourceController
{
    /**
     * @var string
     */
    protected $contentType = 'payments';

    /**
     * @var string
     */
    protected $default_view = 'payments';

    protected $listOrdering = 'p.payment_date';

    protected $listDirection = 'desc';

    protected $listFilters = [
        'client_id' => 'INT',
        'account_id' => 'INT',
        'status' => 'INT',
        'payment_method' => 'STRING',
    ];
}
