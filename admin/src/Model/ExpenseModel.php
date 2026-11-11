<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\CMS\Log\Log;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Expense model.
 *
 * @since  0.0.29
 */
class ExpenseModel extends AdminModel
{
    use VersionableModelTrait;

    /**
     * The type alias for this content type.
     *
     * @var    string
     */
    public $typeAlias = 'com_mothership.expense';

    protected function canDelete($record)
    {
        return $this->getCurrentUser()->authorise('core.delete', 'com_mothership');
    }

    protected function canCheckin($record)
    {
        return $this->getCurrentUser()->authorise('core.manage', 'com_mothership');
    }

    protected function canEdit($record)
    {
        return $this->getCurrentUser()->authorise('core.edit', 'com_mothership');
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_mothership.expense', 'expense', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_mothership.edit.expense.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_mothership.expense', $data);

        return $data;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  A Table object.
     *
     * @return  void
     */
    protected function prepareTable($table)
    {
        if (!empty($table->item)) {
            $table->item = htmlspecialchars_decode($table->item, ENT_QUOTES);
        }
    }

    public function save($data)
    {
        $table = $this->getTable();

        Log::add('Expense data received for saving: ' . json_encode($data), Log::DEBUG, 'com_mothership');

        // Stamp the author on new expenses.
        if (empty($data['id']) && empty($data['created_by'])) {
            $data['created_by'] = (int) $this->getCurrentUser()->id;
        }

        // Start from the stored row so that storing NULLs below only clears what the form cleared.
        if (!empty($data['id']) && !$table->load((int) $data['id'])) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }

        if (!empty($table->id)) {
            $table->updated_at = Factory::getDate()->toSql();
        }

        // Store NULLs too: unassigning an expense empties its client, account or project.
        if (!$table->store(true)) {
            $this->setError($table->getError());
            return false;
        }

        $this->setState($this->getName() . '.id', $table->id);

        return true;
    }

    /**
     * Cancel editing by checking in the record.
     *
     * @param   int|null  $pk  The primary key of the record to check in.
     *
     * @return  bool  True on success, false on failure.
     */
    public function cancelEdit($pk = null)
    {
        $pk = $pk ? $pk : (int) $this->getState($this->getName() . '.id');

        if ($pk) {
            $table = $this->getTable();
            if (!$table->checkin($pk)) {
                $this->setError($table->getError());
                return false;
            }
        }

        return true;
    }
}
