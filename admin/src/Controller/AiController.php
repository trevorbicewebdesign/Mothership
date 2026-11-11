<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiService;

\defined('_JEXEC') or die;

/**
 * AI controller for com_mothership: connection testing.
 */
class AiController extends BaseController
{
    /**
     * Run a live "test connection" against the selected provider's connector
     * and return the outcome as JSON. Called from the Options -> AI page.
     */
    public function test()
    {
        $app = Factory::getApplication();
        $app->getInput()->set('format', 'json');

        // CSRF protection (do not raise; report as JSON). The field sends the
        // token in the X-CSRF-Token header, which checkToken() honours.
        if (!Session::checkToken()) {
            $this->respond(false, 'The security token has expired. Reload the page and try again.');
            return;
        }

        // Only component managers may spend money hitting the API.
        if (!$app->getIdentity()->authorise('core.manage', 'com_mothership')) {
            $this->respond(false, 'You are not permitted to test the AI connection.');
            return;
        }

        $provider = strtolower((string) $app->getInput()->getWord('provider', ''));

        $start  = microtime(true);
        $result = AiService::testConnection($provider);
        $ms     = (int) round((microtime(true) - $start) * 1000);

        if ($result->ok) {
            $model = $result->model ?? '';
            $this->respond(true, 'Connection successful.', [
                'model'      => $model,
                'latency_ms' => $ms,
                'reply'      => $result->text,
            ]);
            return;
        }

        $this->respond(false, $result->error ?? 'The connection test failed.', [
            'latency_ms' => $ms,
        ]);
    }

    /**
     * Emit a JSON response and end the request.
     */
    private function respond(bool $ok, string $message, array $extra = []): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json', true);
        $app->sendHeaders();

        echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));

        $app->close();
    }
}
