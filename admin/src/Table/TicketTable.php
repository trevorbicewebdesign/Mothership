<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Ticket table
 */
class TicketTable extends Table
{
    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var boolean
     */
    protected $_supportNullValue = true;

    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_mothership.ticket';

        parent::__construct('#__mothership_tickets', 'id', $db, $dispatcher);
    }

    /**
     * Overloaded check function.
     *
     * @return boolean True if the object is ok
     */
    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        if (trim((string) $this->subject) === '') {
            $this->setError('Please provide a subject for the ticket.');

            return false;
        }

        if ((int) $this->client_id <= 0) {
            $this->setError('A ticket must belong to a client.');

            return false;
        }

        return true;
    }
}
