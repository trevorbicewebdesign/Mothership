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
 * Clients API controller. filter[search] matches the client name.
 */
class ClientsController extends ResourceController
{
    /**
     * @var string
     */
    protected $contentType = 'clients';

    /**
     * @var string
     */
    protected $default_view = 'clients';

    protected $listOrdering = 'c.name';

    protected $listDirection = 'asc';

    protected $listFilters = [
        'search' => 'STRING',
    ];
}
