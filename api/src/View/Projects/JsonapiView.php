<?php

/**
 * @package     Mothership.API
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Api\View\Projects;

use TrevorBice\Component\Mothership\Api\View\ResourceView;

\defined('_JEXEC') or die;

/**
 * The projects API view.
 */
class JsonapiView extends ResourceView
{
    /**
     * Fields rendered for a single item: columns of the row ProjectModel::getItem() loads.
     *
     * @var array
     */
    protected $fieldsToRenderItem = [
        'id',
        'client_id',
        'account_id',
        'name',
        'description',
        'type',
        'status',
        'metadata',
        'created',
        'created_by',
    ];

    /**
     * Fields rendered for each item in a list. These are columns ProjectsModel::getListQuery()
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
        'name',
        'description',
        'type',
        'metadata',
        'created',
    ];

    protected $jsonFields = ['metadata'];
}
