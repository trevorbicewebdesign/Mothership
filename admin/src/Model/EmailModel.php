<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use TrevorBice\Component\Mothership\Administrator\Service\EmailLog;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * One entry in the email log. Read-only: the log is written by EmailService when mail is sent.
 */
class EmailModel extends BaseDatabaseModel
{
    protected function populateState()
    {
        $this->setState('email.id', Factory::getApplication()->getInput()->getInt('id'));
    }

    public function getItem($pk = null): ?object
    {
        $pk = (int) ($pk ?: $this->getState('email.id'));

        return EmailLog::find($pk);
    }
}
