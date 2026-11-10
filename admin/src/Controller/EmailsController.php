<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\EmailLog;
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;

\defined('_JEXEC') or die;

/**
 * The email log: list, forget, and send again.
 */
class EmailsController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        return parent::display($cachable, $urlparams);
    }

    /**
     * Send the selected entries again, as they were. Each attempt is a new entry pointing back
     * at the one it repeats, so the record of the original failure stays.
     */
    public function resend(): void
    {
        $this->checkToken();

        $app  = Factory::getApplication();
        $ids  = array_values(array_filter(array_map('intval', (array) $this->input->get('cid', [], 'array'))));
        $one  = $this->input->getInt('id');
        $back = $one ? Route::_('index.php?option=com_mothership&view=email&id=' . $one, false) : Route::_('index.php?option=com_mothership&view=emails', false);

        if (empty($ids) && $one) {
            $ids = [$one];
        }

        if (!$app->getIdentity()->authorise('core.edit', 'com_mothership')) {
            $app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
            $this->setRedirect($back);

            return;
        }

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_EMAIL_SELECTED'), 'warning');
            $this->setRedirect($back);

            return;
        }

        $sent   = 0;
        $failed = [];

        foreach ($ids as $id) {
            $row = EmailLog::find($id);

            if (!$row) {
                continue;
            }

            $ok = EmailService::sendEmail(
                self::addresses($row->recipient),
                (string) $row->subject,
                ['html' => (string) $row->body_html],
                [
                    'template'    => $row->template,
                    'object_type' => $row->object_type,
                    'object_id'   => $row->object_id,
                    'client_id'   => $row->client_id,
                    'account_id'  => $row->account_id,
                    'cc'          => self::addresses($row->cc),
                    'bcc'         => self::addresses($row->bcc),
                    'reply_to'    => $row->reply_to,
                    'resend_of'   => $row->id,
                ]
            );

            if ($ok) {
                $sent++;
            } else {
                $failed[] = (string) $row->recipient;
            }
        }

        if ($sent > 0) {
            $app->enqueueMessage(Text::plural('COM_MOTHERSHIP_EMAILS_N_ITEMS_RESENT', $sent), 'message');
        }

        // A failed resend has already put its own warning on screen (EmailService does that in the
        // administrator), so only the count is added here.
        if ($failed) {
            $app->enqueueMessage(Text::plural('COM_MOTHERSHIP_EMAILS_N_ITEMS_RESEND_FAILED', \count($failed)), 'error');
        }

        $this->setRedirect($back);
    }

    /** Forget the selected entries. Nothing already sent is recalled. */
    public function delete(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $ids = array_values(array_filter(array_map('intval', (array) $this->input->get('cid', [], 'array'))));

        if (!$app->getIdentity()->authorise('core.delete', 'com_mothership')) {
            $app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');
        } elseif (empty($ids)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_EMAIL_SELECTED'), 'warning');
        } elseif ($this->getModel('Emails')->delete($ids)) {
            $app->enqueueMessage(Text::plural('COM_MOTHERSHIP_EMAILS_N_ITEMS_DELETED', \count($ids)), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EMAIL_DELETE_FAILED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=emails', false));
    }

    /** "a@x.com, b@y.com" back into the list the mailer takes. */
    private static function addresses(?string $list): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $list))));
    }
}
