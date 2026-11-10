<?php

/**
 * @package     Mothership.API
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Api\View\Domains;

use TrevorBice\Component\Mothership\Api\View\ResourceView;

\defined('_JEXEC') or die;

/**
 * The domains API view.
 */
class JsonapiView extends ResourceView
{
    /**
     * Fields rendered for a single item: columns of the row DomainModel::getItem() loads.
     *
     * @var array
     */
    protected $fieldsToRenderItem = [
        'id',
        'name',
        'client_id',
        'account_id',
        'status',
        'epp_status',
        'registrar',
        'reseller',
        'dns_provider',
        'ns1',
        'ns2',
        'ns3',
        'ns4',
        'purchase_date',
        'expiration_date',
        'auto_renew',
        'notes',
        'created',
        'modified',
    ];

    /**
     * Fields rendered for each item in a list. These are columns DomainsModel::getListQuery()
     * selects; adding anything else here yields null.
     *
     * @var array
     */
    protected $fieldsToRenderList = [
        'id',
        'name',
        'client_id',
        'client_name',
        'account_id',
        'account_name',
        'status',
        'registrar',
        'reseller',
        'dns_provider',
        'created',
    ];

    protected $jsonFields = ['epp_status'];
}
