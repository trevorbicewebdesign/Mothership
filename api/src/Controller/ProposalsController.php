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
 * Proposals API controller. No filters: ProposalsModel applies none.
 */
class ProposalsController extends ResourceController
{
    /**
     * @var string
     */
    protected $contentType = 'proposals';

    /**
     * @var string
     */
    protected $default_view = 'proposals';

    protected $listOrdering = 'proposal.id';

    protected $listDirection = 'desc';
}
