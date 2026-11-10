<?php

/**
 * @package     Mothership.API
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Api\View\Accounts;

use TrevorBice\Component\Mothership\Api\View\ResourceView;

\defined('_JEXEC') or die;

/**
 * The accounts API view.
 */
class JsonapiView extends ResourceView
{
    /**
     * Fields rendered for a single item: columns of the row AccountModel::getItem() loads.
     *
     * @var array
     */
    protected $fieldsToRenderItem = [
        'id',
        'client_id',
        'name',
        'rate',
        'created',
        'created_by',
    ];

    /**
     * Fields rendered for each item in a list. These are columns AccountsModel::getListQuery()
     * selects; adding anything else here yields null.
     *
     * @var array
     */
    protected $fieldsToRenderList = [
        'id',
        'client_id',
        'client_name',
        'name',
        'rate',
        'created',
    ];
}
