<?php

/**
 * @package     Mothership.API
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Api\Controller;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;

\defined('_JEXEC') or die;

/**
 * Base for the read-only resource controllers.
 *
 * displayList() and displayItem() are inherited from ApiController. No write
 * routes are registered by plg_webservices_mothership, so create/update/delete
 * are unreachable even though the parent implements them.
 *
 * Models come from the Administrator namespace via ApiMVCFactory's fallback,
 * so there is no duplicate query logic here.
 */
abstract class ResourceController extends ApiController
{
    /**
     * Column the list is sorted by. ApiController builds the model with
     * ignore_request, so the model's populateState() default never applies.
     *
     * @var string
     */
    protected $listOrdering = 'id';

    /**
     * @var string
     */
    protected $listDirection = 'asc';

    /**
     * filter[...] query parameters this resource accepts, mapped to the type
     * they are cleaned as. Only filters the list model really applies belong
     * here; anything else in the request is ignored.
     *
     * @var array<string, string>
     */
    protected $listFilters = [];

    /**
     * The routes are non-public, so every caller holds a valid token; that only
     * proves who they are. Reading still needs the permission the administrator
     * asks for, otherwise any account with a token could list every client.
     */
    public function execute($task)
    {
        if (!$this->app->getIdentity()->authorise('core.manage', 'com_mothership')) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return parent::execute($task);
    }

    /**
     * @return  static
     */
    public function displayList()
    {
        $requested = $this->input->get('filter', [], 'array');
        $filter    = InputFilter::getInstance();

        foreach ($this->listFilters as $name => $type) {
            if (\array_key_exists($name, $requested)) {
                $this->modelState->set('filter.' . $name, $filter->clean($requested[$name], $type));
            }
        }

        $this->modelState->set('list.ordering', $this->listOrdering);
        $this->modelState->set('list.direction', $this->listDirection);

        return parent::displayList();
    }
}
