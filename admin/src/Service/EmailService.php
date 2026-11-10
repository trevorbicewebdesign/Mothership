<?php

namespace TrevorBice\Component\Mothership\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;

class EmailService
{
    /** Why the last send did not go, in words, or null when it went (or nothing has been sent yet). */
    private static ?string $lastError = null;

    /** What EmailLog records when the mail server returned false without saying why. */
    public const REFUSED = 'The mail server did not accept the message.';

    /** What EmailLog records when a caller asked for the message not to be sent and gave no reason. */
    public const SKIPPED_BY_CHOICE = 'Not sent: the sender chose not to email this.';

    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    /**
     * The options that tie an email to the record it is about, for the email log. Pass the
     * invoice, payment or proposal; its client and account come along.
     *
     *   EmailService::sendTemplate(..., EmailService::about('invoice', $invoice));
     */
    public static function about(string $type, $record, array $more = []): array
    {
        return $more + [
            'object_type' => $type,
            'object_id'   => (int) ($record->id ?? 0),
            'client_id'   => $record->client_id ?? null,
            'account_id'  => $record->account_id ?? null,
        ];
    }

    /**
     * Sends a rendered email using a layout template.
     */
    public static function sendTemplate(string $template, $to, string $subject, array $data = [], array $options = []): bool
    {
        $body = self::generateBody($template, $data);
        return self::sendEmail($to, $subject, $body, $options + ['template' => $template]);
    }

    /**
     * Sends a raw email with provided body content, and records the attempt in the email log.
     *
     * Options, all optional: cc, bcc, reply_to, reply_to_name; for the log: template,
     * object_type, object_id, client_id, account_id (see about()), resend_of; and skip, which
     * records the message as deliberately not sent (a string says why) instead of sending it.
     *
     * A send that fails is recorded, reported to the Joomla log and, in the administrator, shown
     * as a warning. It returns false rather than throwing, so the action that caused it (opening
     * an invoice, confirming a payment) still completes; lastError() has the reason.
     */
    public static function sendEmail($to, string $subject, array $body, array $options = []): bool
    {
        self::$lastError = null;

        $html = (string) ($body['html'] ?? '');
        $text = (string) ($body['text'] ?? strip_tags($html));

        $logId = EmailLog::start([
            'client_id'   => $options['client_id'] ?? null,
            'account_id'  => $options['account_id'] ?? null,
            'object_type' => $options['object_type'] ?? null,
            'object_id'   => $options['object_id'] ?? null,
            'template'    => $options['template'] ?? null,
            'recipient'   => self::addressList($to),
            'cc'          => self::addressList($options['cc'] ?? null),
            'bcc'         => self::addressList($options['bcc'] ?? null),
            'reply_to'    => $options['reply_to'] ?? null,
            'subject'     => $subject,
            'body_html'   => $html,
            'resend_of'   => $options['resend_of'] ?? null,
        ]);

        if (!empty($options['skip'])) {
            EmailLog::skipped($logId, \is_string($options['skip']) ? $options['skip'] : self::SKIPPED_BY_CHOICE);

            return false;
        }

        try {
            /** @var Mail $mailer */
            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            $mailer->addRecipient($to);
            $mailer->setSubject($subject);
            $mailer->isHtml(true);
            $mailer->setBody($html);
            $mailer->AltBody = $text;

            if (!empty($options['cc'])) {
                $mailer->addCc($options['cc']);
            }

            if (!empty($options['bcc'])) {
                $mailer->addBcc($options['bcc']);
            }

            // Replies go here instead of the site's From address.
            if (!empty($options['reply_to'])) {
                $mailer->addReplyTo($options['reply_to'], $options['reply_to_name'] ?? '');
            }

            if ($mailer->Send() === true) {
                EmailLog::sent($logId, $mailer->getLastMessageID() ?: null);

                return true;
            }

            self::fail($logId, self::addressList($to), trim((string) $mailer->ErrorInfo) ?: self::REFUSED);
        } catch (\Throwable $e) {
            self::fail($logId, self::addressList($to), $e->getMessage() ?: \get_class($e));
        }

        return false;
    }

    /** Record a failed send everywhere it should show: the email log, the Joomla log and, in the administrator, on screen. */
    private static function fail(int $logId, string $to, string $error): void
    {
        self::$lastError = $error;
        EmailLog::failed($logId, $error);
        Log::add(sprintf('Email to %s was not sent: %s', $to, $error), Log::ERROR, 'com_mothership');

        try {
            $app = Factory::getApplication();
        } catch (\Throwable $e) {
            return;
        }

        if ($app instanceof CMSApplication && $app->isClient('administrator')) {
            $app->enqueueMessage(
                Text::sprintf(
                    'COM_MOTHERSHIP_EMAIL_FAILED_NOTICE',
                    htmlspecialchars($to, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($error, ENT_QUOTES, 'UTF-8'),
                    Route::_('index.php?option=com_mothership&view=emails&filter[status]=failed', false)
                ),
                'warning'
            );
        }
    }

    /** Addresses as one string for the log: "a@x.com, b@y.com". */
    private static function addressList($addresses): string
    {
        $list = [];

        foreach ((array) $addresses as $address) {
            $address = trim((string) $address);

            if ($address !== '') {
                $list[] = $address;
            }
        }

        return implode(', ', $list);
    }

    /**
     * Generates email body (html + text) from a layout
     */
    public static function generateBody(string $template, array $data = []): array
    {
        $html = self::renderLayout("emails.$template", $data);
        $params = ComponentHelper::getParams('com_mothership');
        
        $data = array_merge($data, [
            'company_name' => $params->get('company_name', ''),
            'company_address' => $params->get('company_address', ''),
            'company_address_1' => $params->get('company_address_1', ''),
            'company_address_2' => $params->get('company_address_2', ''),
            'company_city' => $params->get('company_city', ''),
            'company_state' => $params->get('company_state', ''),
            'company_zip' => $params->get('company_zip', ''),
            'company_phone' => $params->get('company_phone', ''),
            'company_email' => $params->get('company_email', ''),
        ]);
        $text = strip_tags($html);
        $html = self::renderLayout('emails.wrapper', [
            'content' => $html,
            'data' => $data
        ]);

        return [
            'html' => $html,
            'text' => $text
        ];
    }

    private static function renderLayout(string $layoutName, array $data): string
    {
        $layout = new FileLayout($layoutName, \JPATH_ROOT . '/administrator/components/com_mothership/layouts');
        return $layout->render($data);
    }

    private static function layoutExists(string $layoutName): bool
    {
        $file = \JPATH_ROOT . '/administrator/components/com_mothership/layouts/' . str_replace('.', '/', $layoutName) . '.php';
        return file_exists($file);
    }
}
