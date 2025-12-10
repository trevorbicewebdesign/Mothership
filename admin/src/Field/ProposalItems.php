<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;

\defined('_JEXEC') or die;

/**
 * Custom field to display invoice items in a table layout.
 */
class proposalitemsField extends SubformField
{
    protected $type = 'proposalitems';

    /**
     * Override the getInput method to include a custom layout.
     */
    protected function getInput()
    {
        $layoutPath = JPATH_ROOT . '/administrator/components/com_mothership/tmpl/fields/proposalitems.php';

        if (!file_exists($layoutPath)) {
            return parent::getInput(); // Fallback to default Joomla subform
        }

        $layout = new FileLayout('proposalitems',JPATH_ROOT . '/administrator/components/com_mothership/tmpl/fields');

        return $layout->render(['field' => $this]);
    }
}
