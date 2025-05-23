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
 * Project model.
 *
 * @since  1.6
 */
class ProjectModel extends AdminModel
{
    use VersionableModelTrait;

    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  3.2
     */
    public $typeAlias = 'com_mothership.project';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   1.6
     */
    protected function canDelete($record)
    {
        if (empty($record->id) || $record->state != -2) {
            return false;
        }

        if (!empty($record->catid)) {
            return $this->getCurrentUser()->authorise('core.delete', 'com_mothership.category.' . (int) $record->catid);
        }

        return parent::canDelete($record);
    }

    /**
     * Checks if the current user has permission to check in the record.
     *
     * @param mixed $record The record to check in.
     * @return bool True if the user has the 'core.manage' permission for 'com_mothership', false otherwise.
     */
    protected function canCheckin($record)
    {
        return $this->getCurrentUser()->authorise('core.manage', 'com_mothership');
    }

    /**
     * Checks if the current user has permission to edit the given record.
     *
     * @param mixed $record The record to check edit permissions for.
     * @return bool True if the user has edit permissions, false otherwise.
     */
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
     *
     * @since   1.6
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_mothership.project', 'project', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.6
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_mothership.edit.project.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        // Decode the metadata JSON into an array so the form can populate it
        if (isset($data->metadata) && is_string($data->metadata)) {
            $decoded = json_decode($data->metadata, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data->metadata = $decoded;
            }
        }

        $this->preprocessData('com_mothership.project', $data);

        return $data;
    }


    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  A Table object.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function prepareTable($table)
    {
        $table->name = htmlspecialchars_decode($table->name, ENT_QUOTES);
    }

    protected function preprocessForm(\Joomla\CMS\Form\Form $form, $data, $group = 'content')
    {
        parent::preprocessForm($form, $data, $group);

        // Merge in additional metadata fields
        \Joomla\CMS\Form\Form::addFormPath(JPATH_ROOT . '/administrator/component/com_mothership/src/models/forms');
        $form->loadFile('project-website', false);
    }


    public function save($data)
    {
        $table = $this->getTable();

        // Convert metadata array to JSON
        if( is_object($data) && (isset($data->metadata) && is_array($data->metadata))) {
            $data->metadata = json_encode($data->metadata);
        }

        if(is_array($data) && isset($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }
        

        if (!$table->bind($data)) {
            $error = $table->getError();
            Log::add('Bind failed: ' . $error, Log::ERROR, 'com_mothership');
            $this->setError($error);
            return false;
        }

        if (empty($table->created)) {
            $table->created = Factory::getDate()->toSql();
        }

        if (!$table->check()) {
            $error = $table->getError();
            Log::add('Check failed: ' . $error, Log::ERROR, 'com_mothership');
            $this->setError($error);
            return false;
        }

        if (!$table->store()) {
            $error = $table->getError();
            Log::add('Store failed: ' . $error, Log::ERROR, 'com_mothership');
            $this->setError($error);
            return false;
        }

        // Set the new record ID into the model state
        $this->setState($this->getName() . '.id', $table->id);

        return true;
    }

     /**
     * Cancel editing by checking in the record.
     *
     * @param   int|null  $pk  The primary key of the record to check in. If null, it attempts to load it from the state.
     *
     * @return  bool  True on success, false on failure.
     */
    public function cancelEdit($pk = null)
    {
        // Use the provided primary key or retrieve it from the model state
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
