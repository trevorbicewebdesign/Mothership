<?php

/**
 * @package     Mothership.API
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Api\View;

use Joomla\CMS\MVC\View\JsonApiView;

\defined('_JEXEC') or die;

/**
 * Base for the resource views. Evens out the two differences between what a
 * list model and an item model return for the same record, so a field means
 * the same thing in both responses.
 */
abstract class ResourceView extends JsonApiView
{
    /**
     * Columns stored as JSON text, rendered as JSON rather than as a string.
     *
     * @var string[]
     */
    protected $jsonFields = [];

    /**
     * The label for a numeric status, or null when the resource's status is
     * not numeric. The list queries already select the label; the item models
     * return the number, so items are translated here to match.
     */
    protected function statusLabel(int $status): ?string
    {
        return null;
    }

    /**
     * @param   object  $item  The record, from either model.
     *
     * @return  object
     */
    protected function prepareItem($item)
    {
        if (isset($item->status) && is_numeric($item->status)) {
            $item->status = $this->statusLabel((int) $item->status) ?? $item->status;
        }

        foreach ($this->jsonFields as $field) {
            if (isset($item->$field) && \is_string($item->$field)) {
                $item->$field = json_decode($item->$field);
            }
        }

        return parent::prepareItem($item);
    }
}
