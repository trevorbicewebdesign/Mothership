<?php

namespace TrevorBice\Component\Mothership\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

\defined('_JEXEC') or die;

/**
 * Account list for the client/account list filter, scoped to the currently-
 * selected client (so picking a client narrows the account choices). The client
 * is read from the shared cross-list scope, so this field works on every list
 * that uses the shared Client/Account filter (invoices, projects, domains,
 * payments).
 */
class AccountfilterField extends ListField
{
    protected $type = 'accountfilter';

    public function getOptions()
    {
        $app    = Factory::getApplication();
        $filter = (array) $app->getInput()->get('filter', [], 'array');

        // Current client filter: the just-submitted value, else the shared scope.
        $clientId = (isset($filter['client_id']) && $filter['client_id'] !== '')
            ? (int) $filter['client_id']
            : (int) $app->getUserState('com_mothership.filter.client_id', 0);

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('name')])
            ->from($db->quoteName('#__mothership_accounts'))
            ->order($db->quoteName('name') . ' ASC');

        if ($clientId > 0) {
            $query->where($db->quoteName('client_id') . ' = ' . $clientId);
        }

        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        $options = [HTMLHelper::_('select.option', '', Text::_('COM_MOTHERSHIP_FILTER_SELECT_ACCOUNT'))];
        foreach ($rows as $r) {
            $options[] = HTMLHelper::_('select.option', $r->id, $r->name);
        }

        return array_merge(parent::getOptions(), $options);
    }
}
