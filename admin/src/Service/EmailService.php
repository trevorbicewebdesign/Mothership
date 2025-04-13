<?php

namespace TrevorBice\Component\Mothership\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Mail\Mail;

class EmailService
{
    /**
     * Sends a rendered email using a layout template.
     */
    public static function sendTemplate(string $template, $to, string $subject, array $data = [], array $options = []): bool
    {
        $body = self::generateBody($template, $data);
        return self::sendEmail($to, $subject, $body, $options);
    }

    /**
     * Sends a raw email with provided body content.
     */
    public static function sendEmail($to, string $subject, array $body, array $options = []): bool
    {
        /** @var Mail $mailer */
        $mailer = Factory::getMailer();
        $mailer->addRecipient($to);
        $mailer->setSubject($subject);
        $mailer->isHtml(true);
        $mailer->setBody($body['html'] ?? '');
        $mailer->AltBody = $body['text'] ?? strip_tags($body['html'] ?? '');

        if (!empty($options['cc'])) {
            $mailer->addCc($options['cc']);
        }

        if (!empty($options['bcc'])) {
            $mailer->addBcc($options['bcc']);
        }

        return $mailer->Send();
    }

    /**
     * Generates email body (html + text) from a layout
     */
    public static function generateBody(string $template, array $data = []): array
    {
        $html = self::renderLayout("emails.$template", $data);

        $textLayout = "emails.$template.text";
        $text = self::layoutExists($textLayout)
            ? self::renderLayout($textLayout, $data)
            : strip_tags($html);

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
