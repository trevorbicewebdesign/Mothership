<?php

namespace TrevorBice\Component\Mothership\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Mail\Mail;

/**
 * Service to send templated emails for Mothership
 */
class EmailService
{
    /**
     * Sends an email using a Joomla layout template.
     *
     * @param string $template   The name of the layout file (no extension)
     * @param string|array $to   Email address(es) to send to
     * @param string $subject    Email subject
     * @param array  $data       Data passed to layout
     * @param array  $options    ['cc' => [], 'bcc' => []]
     *
     * @return boolean
     */
    public static function sendTemplate(string $template, $to, string $subject, array $data = [], array $options = []): bool
    {
        
        $htmlBody = self::renderLayout("emails.$template", $data);
        $htmlBody = "This is a test";
        
        $textLayout = "emails.$template.text";
        //$textBody = self::layoutExists($textLayout) ? self::renderLayout($textLayout, $data) : strip_tags($htmlBody);
        $textBody = "This is a test";
       

        /** @var Mail $mailer */
        $mailer = Factory::getMailer();
        $mailer->addRecipient($to);
        $mailer->setSubject($subject);
        $mailer->isHtml(true);
        $mailer->setBody($htmlBody);
        $mailer->AltBody = $textBody;

        if (!empty($options['cc'])) {
            $mailer->addCc($options['cc']);
        }

        if (!empty($options['bcc'])) {
            $mailer->addBcc($options['bcc']);
        }

        return $mailer->Send();
    }

    /**
     * Renders a Joomla layout with given data
     *
     * @param string $layoutName Dot-separated layout path (e.g. 'emails.invoice')
     * @param array  $data
     *
     * @return string
     */
    private static function renderLayout(string $layoutName, array $data): string
    {
        $layout = new FileLayout($layoutName, \JPATH_ROOT . '/administrator/components/com_mothership/layouts');
        return $layout->render($data);
    }

    /**
     * Checks if a layout file exists
     *
     * @param string $layoutName
     * @return bool
     */
    private static function layoutExists(string $layoutName): bool
    {
        $file = \JPATH_ROOT . '/administrator/components/com_mothership/layouts/' . str_replace('.', '/', $layoutName) . '.php';
        return file_exists($file);
    }
}
