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
 * Domains API controller. filter[search] matches the domain name; filter[client_id], filter[account_id].
 */
class DomainsController extends ResourceController
{
    /**
     * @var string
     */
    protected $contentType = 'domains';

    /**
     * @var string
     */
    protected $default_view = 'domains';

    protected $listOrdering = 'd.name';

    protected $listDirection = 'asc';

    protected $listFilters = [
        'search' => 'STRING',
        'client_id' => 'INT',
        'account_id' => 'INT',
    ];
}
