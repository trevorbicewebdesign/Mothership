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
 * Projects API controller. filter[client_id], filter[account_id].
 */
class ProjectsController extends ResourceController
{
    /**
     * @var string
     */
    protected $contentType = 'projects';

    /**
     * @var string
     */
    protected $default_view = 'projects';

    protected $listOrdering = 'p.name';

    protected $listDirection = 'asc';

    protected $listFilters = [
        'client_id' => 'INT',
        'account_id' => 'INT',
    ];
}
