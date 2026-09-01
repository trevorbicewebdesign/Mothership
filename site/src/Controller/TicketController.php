<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class TicketController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->input->set('view', $this->input->getCmd('view', 'ticket'));
        parent::display($cachable, $urlparams);
    }

    /** Create a new ticket from the portal "New ticket" form. */
    public function save()
    {
        $this->checkToken();

        $app   = $this->app;
        $input = $app->getInput();
        $user  = $app->getIdentity();

        $subject   = trim($input->getString('subject', ''));
        $desc      = $input->get('description', '', 'RAW');
        $priority  = $input->getCmd('priority', 'normal');
        $accountId = $input->getInt('account_id') ?: null;
        $refUrl     = trim($input->getString('reference_url', ''));
        $projectRaw = $input->getString('project_id', '');
        $newProject = ($projectRaw === 'new');
        $projectId  = (!$newProject && ctype_digit($projectRaw) && (int) $projectRaw > 0) ? (int) $projectRaw : null;
        $clientId   = (int) MothershipHelper::getUserClientId();

        // "New project" request: no existing project, flag the ticket type, and
        // surface the proposed name at the top of the description for triage.
        if ($newProject) {
            $name = trim($input->getString('new_project_name', ''));
            $lead = $name !== '' ? 'Proposed new project: ' . $name : 'Requesting a new project.';
            $desc = $lead . "\n\n" . $desc;
        }

        // Ownership guard: a client may only attach their own account/project. The
        // form only offers their own, but a crafted POST could inject a foreign id —
        // silently drop anything that doesn't belong to this client.
        $db = Factory::getContainer()->get('DatabaseDriver');
        if ($accountId) {
            $ok = (int) $db->setQuery(
                $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__mothership_accounts'))
                    ->where('id = ' . (int) $accountId)->where('client_id = ' . $clientId)
            )->loadResult();
            if (!$ok) {
                $accountId = null;
            }
        }
        if ($projectId) {
            $q = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__mothership_projects'))
                ->where('id = ' . (int) $projectId)->where('client_id = ' . $clientId);
            if ($accountId) {
                $q->where('account_id = ' . (int) $accountId);
            }
            if (!(int) $db->setQuery($q)->loadResult()) {
                $projectId = null;
            }
        }

        if ($subject === '') {
            $app->enqueueMessage('Please enter a subject for your ticket.', 'warning');
            $app->redirect(Route::_('index.php?option=com_mothership&view=tickets&layout=create', false));
            return;
        }

        $now = Factory::getDate()->toSql();
        $row = (object) [
            'client_id'     => $clientId,
            'account_id'    => $accountId,
            'project_id'    => $projectId,
            'type'          => $newProject ? 'New Project' : null,
            'subject'       => $subject,
            'description'   => $desc,
            'reference_url' => $refUrl !== '' ? $refUrl : null,
            'status'        => 'new',
            'priority'    => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
            'created_by'  => (int) $user->id,
            'created'     => $now,
        ];
        Factory::getContainer()->get('DatabaseDriver')->insertObject('#__mothership_tickets', $row, 'id');

        $this->saveAttachments($input, 'ticket', (int) $row->id, (int) $user->id);

        $app->enqueueMessage('Your ticket has been created.', 'success');
        $app->redirect(Route::_('index.php?option=com_mothership&view=ticket&id=' . (int) $row->id, false));
    }

    /** Add a reply, and (for admins) optionally change the status. */
    public function reply()
    {
        $this->checkToken();

        $app   = $this->app;
        $input = $app->getInput();
        $user  = $app->getIdentity();
        $db    = Factory::getContainer()->get('DatabaseDriver');

        $ticketId = $input->getInt('ticket_id');
        $ticket   = $db->setQuery(
            $db->getQuery(true)->select('*')->from('#__mothership_tickets')->where('id = ' . (int) $ticketId)
        )->loadObject();

        if (!$ticket) {
            throw new \Exception('Ticket not found', 404);
        }

        $clientIds = MothershipHelper::getUserClientIds();
        $isAdmin   = (bool) $user->authorise('core.admin', 'com_mothership');
        $owns      = $clientIds && in_array((int) $ticket->client_id, $clientIds, true);

        if (!$owns && !$isAdmin) {
            throw new \Exception('You are not allowed to reply to this ticket.', 403);
        }

        $now  = Factory::getDate()->toSql();
        $body = $input->get('body', '', 'RAW');

        if (trim($body) !== '') {
            $comment = (object) [
                'context'     => 'ticket',
                'resource_id' => (int) $ticketId,
                'user_id'     => (int) $user->id,
                'body'        => $body,
                'is_internal' => $isAdmin ? (int) $input->getBool('is_internal', false) : 0,
                'created'     => $now,
            ];
            $db->insertObject('#__mothership_comments', $comment);
        }

        if ($isAdmin) {
            $status = $input->getCmd('status', '');
            $valid  = ['new', 'open', 'in_progress', 'waiting', 'resolved', 'closed'];
            if ($status !== '' && in_array($status, $valid, true)) {
                $upd = (object) ['id' => (int) $ticketId, 'status' => $status, 'modified' => $now];
                if (in_array($status, ['resolved', 'closed'], true)) {
                    $upd->closed_at = $now;
                }
                $db->updateObject('#__mothership_tickets', $upd, 'id');
            }
        } elseif (in_array($ticket->status, ['resolved', 'closed'], true)) {
            // A client replying to a resolved/closed ticket reopens it.
            $reopen = (object) ['id' => (int) $ticketId, 'status' => 'open', 'modified' => $now];
            $db->updateObject('#__mothership_tickets', $reopen, 'id');
        }

        $this->saveAttachments($input, 'ticket', (int) $ticketId, (int) $user->id);

        $app->enqueueMessage('Your reply has been added.', 'success');
        $app->redirect(Route::_('index.php?option=com_mothership&view=ticket&id=' . (int) $ticketId, false));
    }

    /**
     * Absolute path to the attachment store, kept ABOVE the web root so files
     * can never be fetched directly — every download goes through attachment()
     * where ownership is enforced. dirname(JPATH_ROOT) is the folder containing
     * /public, e.g. .../webdev/app/mothership/tickets/uploads.
     */
    protected static function attachmentBasePath(): string
    {
        return dirname(JPATH_ROOT) . '/mothership/tickets/uploads';
    }

    /**
     * Stream a ticket attachment to the browser after checking that the current
     * user owns the parent ticket (or is an admin). Route:
     *   index.php?option=com_mothership&task=ticket.attachment&id=<attachmentId>
     */
    public function attachment()
    {
        $app  = $this->app;
        $user = $app->getIdentity();
        $db   = Factory::getContainer()->get('DatabaseDriver');

        $attId = (int) $app->getInput()->getInt('id');
        if (!$attId) {
            throw new \Exception('Attachment not found', 404);
        }

        $att = $db->setQuery(
            $db->getQuery(true)
                ->select('*')
                ->from('#__mothership_attachments')
                ->where('id = ' . $attId)
                ->where('context = ' . $db->quote('ticket'))
        )->loadObject();

        if (!$att) {
            throw new \Exception('Attachment not found', 404);
        }

        $ticket = $db->setQuery(
            $db->getQuery(true)
                ->select('id, client_id')
                ->from('#__mothership_tickets')
                ->where('id = ' . (int) $att->resource_id)
        )->loadObject();

        // Ownership: the client that owns the ticket, or a super user. Anything
        // else is a 404 (not 403) so we don't confirm the attachment exists.
        $clientIds = MothershipHelper::getUserClientIds();
        $owns      = $ticket && $clientIds && in_array((int) $ticket->client_id, $clientIds, true);
        $isAdmin   = (bool) $user->authorise('core.admin', 'com_mothership');

        if (!$owns && !$isAdmin) {
            throw new \Exception('Attachment not found', 404);
        }

        // Resolve the file safely (basename guards against path traversal).
        $path = self::attachmentBasePath() . '/' . basename((string) $att->filename);
        if (!is_file($path)) {
            throw new \Exception('Attachment not found', 404);
        }

        // Only serve known image types; fall back to a safe generic type.
        $allowedMime = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        $mime        = in_array($att->mime, $allowedMime, true) ? $att->mime : 'application/octet-stream';
        $safeName    = preg_replace('/[\r\n"]+/', '', (string) ($att->original_name ?: $att->filename));

        $app->setHeader('Content-Type', $mime, true);
        $app->setHeader('Content-Length', (string) filesize($path), true);
        $app->setHeader('Content-Disposition', 'inline; filename="' . $safeName . '"', true);
        $app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $app->setHeader('Cache-Control', 'private, max-age=0, no-cache', true);
        $app->sendHeaders();

        readfile($path);
        $app->close();
    }

    /**
     * Store uploaded image attachments (drag-and-drop screenshots) against a
     * resource in the shared attachments table. Images only, 8MB cap each.
     */
    protected function saveAttachments($input, $context, $resourceId, $userId)
    {
        $files = $input->files->get('attachments', []);
        if (empty($files)) {
            return;
        }
        if (isset($files['name'])) {           // single-file shape → normalise to a list
            $files = [$files];
        }

        $base = self::attachmentBasePath();
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
        }

        $db      = Factory::getContainer()->get('DatabaseDriver');
        $now     = Factory::getDate()->toSql();
        $allowed = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

        foreach ($files as $f) {
            if (empty($f['tmp_name']) || ($f['error'] ?? 1) !== UPLOAD_ERR_OK) {
                continue;
            }
            if (($f['size'] ?? 0) > 8 * 1024 * 1024) {
                continue; // 8MB cap
            }
            $info = @getimagesize($f['tmp_name']);
            if (!$info || !isset($allowed[$info[2]])) {
                continue; // images only
            }
            $stored = bin2hex(random_bytes(16)) . '.' . $allowed[$info[2]];
            if (@move_uploaded_file($f['tmp_name'], $base . '/' . $stored)) {
                $attachment = (object) [
                    'context'       => $context,
                    'resource_id'   => (int) $resourceId,
                    'filename'      => $stored,
                    'original_name' => substr((string) ($f['name'] ?? ''), 0, 255),
                    'mime'          => $info['mime'] ?? '',
                    'size'          => (int) ($f['size'] ?? 0),
                    'uploaded_by'   => (int) $userId,
                    'created'       => $now,
                ];
                $db->insertObject('#__mothership_attachments', $attachment);
            }
        }
    }
}
