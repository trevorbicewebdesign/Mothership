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
 * Invoices API controller. filter[client_id], filter[account_id].
 */
class InvoicesController extends ResourceController
{
    /**
     * @var string
     */
    protected $contentType = 'invoices';

    /**
     * @var string
     */
    protected $default_view = 'invoices';

    protected $listOrdering = 'i.id';

    protected $listDirection = 'desc';

    protected $listFilters = [
        'client_id' => 'INT',
        'account_id' => 'INT',
    ];
}
