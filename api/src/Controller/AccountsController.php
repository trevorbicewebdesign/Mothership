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
 * Accounts API controller. filter[search] matches the account name.
 */
class AccountsController extends ResourceController
{
    /**
     * @var string
     */
    protected $contentType = 'accounts';

    /**
     * @var string
     */
    protected $default_view = 'accounts';

    protected $listOrdering = 'a.name';

    protected $listDirection = 'asc';

    protected $listFilters = [
        'search' => 'STRING',
    ];
}
