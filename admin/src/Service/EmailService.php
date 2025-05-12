<?php

namespace TrevorBice\Component\Mothership\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Component\ComponentHelper;

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

        $html = self::renderLayout('emails.wrapper', [
            'content' => $html,
            'data' => $data
        ]);

        // $text should be generated from the HTML layout, but for now, we will just use the HTML content.
        $text = strip_tags($html);

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
