<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ProjectHelper;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiService;
use TrevorBice\Component\Mothership\Administrator\Service\ExpenseReader;
use TrevorBice\Component\Mothership\Administrator\Service\ExpenseReceipt;

\defined('_JEXEC') or die;

/**
 * Expense Controller for com_mothership
 */
class ExpenseController extends FormController
{
    protected $default_view = 'expense';

    /** User state: the uploaded files still to be entered, ['tokens' => string[], 'total' => int, 'current' => int]. */
    public const QUEUE = 'com_mothership.expense.import.queue';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function save($key = null, $urlVar = null)
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();
        $data  = $input->get('jform', [], 'array');
        $model = $this->getModel('Expense');

        if (!$model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=expense&layout=edit', false));
            return false;
        }

        // A new expense made from an uploaded invoice keeps the file as its receipt.
        if (empty($data['id']) && !empty($data['receipt_import'])) {
            $newId = (int) $model->getState($model->getName() . '.id');

            if (!ExpenseReceipt::attach($newId, (string) $data['receipt_import'], (int) $app->getIdentity()->id)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_RECEIPT_LOST'), 'warning');
            }
        }

        $app->setUserState('com_mothership.edit.expense.data', null);

        $label = trim(($data['item'] ?? '') !== '' ? $data['item'] : ($data['merchant'] ?? ''));
        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_SAVED_SUCCESSFULLY', '<strong>' . htmlspecialchars($label) . '</strong>'), 'message');

        $task = $input->getCmd('task');

        // More uploaded files are waiting: the next one's form opens. "Save" stays on this
        // expense, and the next opens when it is closed.
        if ($task !== 'apply' && $this->openNext()) {
            return true;
        }

        if ($task === 'apply') {
            $id = !empty($data['id']) ? $data['id'] : $model->getState($model->getName() . '.id');
            $redirect = Route::_("index.php?option=com_mothership&view=expense&layout=edit&id={$id}", false);
        } else {
            $redirect = MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=expenses', false));
        }

        $this->setRedirect($redirect);
        return true;
    }

    public function cancel($key = null)
    {
        $model = $this->getModel('Expense');
        $id    = $this->input->getInt('id');
        $model->cancelEdit($id);

        $app  = Factory::getApplication();
        $data = (array) $app->getUserState('com_mothership.edit.expense.data', []);

        // Closing a form opened from an upload leaves that file out.
        ExpenseReceipt::forget([(string) ($data['receipt_import'] ?? '')]);
        $app->setUserState('com_mothership.edit.expense.data', null);

        if ($this->openNext()) {
            return true;
        }

        $redirect = MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=expenses', false));
        $this->setRedirect($redirect);

        return true;
    }

    public function delete()
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();
        $model = $this->getModel('Expense');
        $cid   = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_EXPENSE_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect(MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=expenses', false)));
    }

    // Returns a list of accounts for a given client in JSON format (cascade).
    public function getAccountsList()
    {
        $client_id   = Factory::getApplication()->getInput()->getInt('client_id');
        $accountList = AccountHelper::getAccountListOptions($client_id);
        echo json_encode($accountList);
        Factory::getApplication()->close();
    }

    // Returns a list of projects for a given account in JSON format (cascade).
    public function getProjectsList()
    {
        $account_id  = Factory::getApplication()->getInput()->getInt('account_id');
        $projectList = ProjectHelper::getProjectListOptions($account_id);
        echo json_encode($projectList);
        Factory::getApplication()->close();
    }

    /**
     * "New from PDF": take one or more uploaded invoices or receipts and open the new-expense form
     * filled in from the first. Each file is parked, and read only when its turn comes; saving or
     * closing a form opens the next file's, until the queue is empty. Nothing is saved until the
     * administrator saves a form; that form's file then becomes the expense's receipt. With AI
     * off the forms open empty and the files are still kept.
     */
    public function importReceipt()
    {
        $this->checkToken();

        $app  = Factory::getApplication();
        $back = Route::_('index.php?option=com_mothership&view=expenseimport', false);

        if (!$app->getIdentity()->authorise('core.create', 'com_mothership')) {
            throw new \Joomla\CMS\Access\Exception\NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        // 'raw': the files are only ever read and served back to administrators, never run.
        $files = $app->getInput()->files->get('receipt', [], 'raw');
        $files = isset($files['tmp_name']) ? [$files] : (array) $files;
        $files = array_values(array_filter($files, static fn ($f) => \is_array($f) && !empty($f['tmp_name'])));

        if (!$files) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_NO_FILE'), 'warning');
            $this->setRedirect($back);
            return false;
        }

        if (\count($files) > ExpenseReceipt::MAX_FILES) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_TOO_MANY', ExpenseReceipt::MAX_FILES), 'warning');
            $this->setRedirect($back);
            return false;
        }

        $tokens  = [];
        $seen    = [];
        $already = 0;

        foreach ($files as $file) {
            $name = htmlspecialchars((string) ($file['name'] ?? ''));

            if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_FILE_FAILED', $name), 'warning');
                continue;
            }

            if ((int) ($file['size'] ?? 0) > ExpenseReader::MAX_BYTES) {
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_TOO_BIG', $name), 'warning');
                continue;
            }

            if (ExpenseReader::sniff($file['tmp_name']) === null) {
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_WRONG_TYPE', $name), 'warning');
                continue;
            }

            if ($carrying = ExpenseReceipt::carrying($file['tmp_name'])) {
                $link = Route::_('index.php?option=com_mothership&view=expense&layout=edit&id=' . $carrying[0]);
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_DUPLICATE', $name, '<a href="' . $link . '">#' . $carrying[0] . '</a>'), 'warning');
                $already = $already ?: $carrying[0];
                continue;
            }

            // The same file chosen twice in one upload is one expense.
            $hash = sha1_file($file['tmp_name']);

            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;

            try {
                $tokens[] = ExpenseReceipt::stash($file['tmp_name'], (string) ($file['name'] ?? ''));
            } catch (\RuntimeException $e) {
                // The folder above the web root could not be made or written to.
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_NO_STORE', htmlspecialchars(ExpenseReceipt::storePath())), 'error');
                $this->setRedirect($back);

                return false;
            }
        }

        $app->setUserState(self::QUEUE, ['tokens' => $tokens, 'total' => \count($tokens)]);

        if ($this->openNext()) {
            return true;
        }

        // Nothing new to enter. A single file we already have opens the expense that has it.
        $this->setRedirect(\count($files) === 1 && $already
            ? Route::_('index.php?option=com_mothership&view=expense&layout=edit&id=' . $already, false)
            : $back);

        return false;
    }

    /** Stop working through the uploaded files. Those not yet entered are dropped. */
    public function importStop()
    {
        $this->checkToken('get');

        $app   = Factory::getApplication();
        $queue = (array) $app->getUserState(self::QUEUE, []);
        $data  = (array) $app->getUserState('com_mothership.edit.expense.data', []);

        ExpenseReceipt::forget(array_merge((array) ($queue['tokens'] ?? []), [(string) ($data['receipt_import'] ?? '')]));

        $app->setUserState(self::QUEUE, null);
        $app->setUserState('com_mothership.edit.expense.data', null);
        $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_STOPPED'), 'message');
        $this->setRedirect(Route::_('index.php?option=com_mothership&view=expenses', false));

        return true;
    }

    /**
     * Open the new-expense form for the next uploaded file waiting its turn: read it, fill the
     * form from it, and say what to check. False when no file is waiting.
     */
    private function openNext(): bool
    {
        $app   = Factory::getApplication();
        $queue = (array) $app->getUserState(self::QUEUE, []);
        $total = (int) ($queue['total'] ?? 0);

        // Most saves have no upload behind them, and so no queue at all.
        $queue['tokens'] = array_values((array) ($queue['tokens'] ?? []));

        do {
            $token  = (string) array_shift($queue['tokens']);
            $parked = $token !== '' ? ExpenseReceipt::pending($token) : null;
        } while ($parked === null && !empty($queue['tokens']));

        if ($parked === null) {
            $app->setUserState(self::QUEUE, null);

            return false;
        }

        $queue['current'] = $total - \count($queue['tokens']);
        $app->setUserState(self::QUEUE, $queue);

        $data = ['receipt_import' => $token];

        if (!AiService::isEnabled()) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_AI_OFF'), 'notice');
        } else {
            $draft = ExpenseReader::read($parked->path);

            if (!$draft['ok']) {
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_NOT_READ', htmlspecialchars((string) $draft['error'])), 'warning');
            } else {
                $filled = ExpenseReceipt::prefill($draft, $token);
                $data   = $filled['data'];

                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_READ'), 'message');

                if ($filled['foreign'] !== null) {
                    $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_FOREIGN', htmlspecialchars($filled['foreign']), ExpenseReceipt::HOME_CURRENCY), 'warning');
                }

                if ($filled['history']) {
                    $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_HISTORY', htmlspecialchars($data['merchant'])), 'notice');
                }

                foreach (ExpenseReceipt::similar($draft) as $like) {
                    $link = Route::_('index.php?option=com_mothership&view=expense&layout=edit&id=' . (int) $like->id);
                    $app->enqueueMessage(Text::sprintf(
                        'COM_MOTHERSHIP_EXPENSE_IMPORT_SIMILAR',
                        '<a href="' . $link . '" target="_blank" rel="noopener">#' . (int) $like->id . ' ' . htmlspecialchars((string) $like->item) . '</a>',
                        htmlspecialchars((string) $like->expense_date),
                        number_format((float) $like->amount, 2)
                    ), 'warning');
                }
            }
        }

        $app->setUserState('com_mothership.edit.expense.data', $data);
        $this->setRedirect(Route::_('index.php?option=com_mothership&view=expense&layout=edit', false));

        return true;
    }

    /** Show a receipt: one kept on an expense (attachment_id), or one still parked (token). */
    public function receipt()
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

        if (!$app->getIdentity()->authorise('core.manage', 'com_mothership')) {
            throw new \Joomla\CMS\Access\Exception\NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        if ($token = $input->getCmd('token', '')) {
            $parked = ExpenseReceipt::pending($token);
            $path   = $parked->path ?? null;
            $mime   = $parked->mime ?? '';
            $name   = $parked->name ?? '';
        } else {
            $receipt = ExpenseReceipt::find($input->getInt('attachment_id'));
            $path    = $receipt->path ?? null;
            $mime    = (string) ($receipt->mime ?? '');
            $name    = (string) ($receipt->original_name ?? '');
        }

        if (!$path || !isset(ExpenseReader::TYPES[$mime])) {
            throw new \Exception(Text::_('COM_MOTHERSHIP_EXPENSE_RECEIPT_MISSING'), 404);
        }

        $name = preg_replace('/[\r\n"]+/', '', $name);

        $app->setHeader('Content-Type', $mime, true);
        $app->setHeader('Content-Length', (string) filesize($path), true);
        $app->setHeader('Content-Disposition', 'inline; filename="' . $name . '"', true);
        $app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $app->setHeader('Cache-Control', 'private, max-age=0, no-cache', true);
        $app->sendHeaders();

        readfile($path);
        $app->close();
    }
}
