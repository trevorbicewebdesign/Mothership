<?php
/**
 * @package     Mothership
 * @subpackage  Plugin.Mothership-Ai.Claude
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Mothership AI connector for Anthropic's Claude.
 *
 * A "connector" plugin: it knows how to talk to one AI provider and nothing
 * about Mothership's domain. The component's AiService selects it (by the
 * ai_provider setting) and calls chat(). Credentials and model come in via
 * $options from the component settings; the plugin can also fall back to its
 * own params so it works standalone.
 *
 * Uses the Anthropic Messages API: POST https://api.anthropic.com/v1/messages
 */
class PlgMothershipAiClaude extends CMSPlugin
{
    protected $autoloadLanguage = true;

    /** Default Anthropic API base (overridable via options/params). */
    private const DEFAULT_BASE_URL = 'https://api.anthropic.com';

    /** Anthropic API version header. */
    private const ANTHROPIC_VERSION = '2023-06-01';

    /**
     * List prices in USD per million tokens, as of October 2026. The "Rates" setting overrides
     * or adds to these, so a price change needs no code change.
     */
    private const RATES = [
        'claude-fable-5-1'  => ['input' => 10.00, 'output' => 50.00],
        'claude-fable-5'    => ['input' => 10.00, 'output' => 50.00],
        'claude-opus-5-5'   => ['input' => 4.00, 'output' => 20.00],
        'claude-opus-5'     => ['input' => 5.00, 'output' => 25.00],
        'claude-sonnet-5-5' => ['input' => 2.00, 'output' => 10.00],
        'claude-sonnet-5'   => ['input' => 2.00, 'output' => 10.00],
        'claude-haiku-5-5'  => ['input' => 0.10, 'output' => 0.50],
        'claude-haiku-4-5'  => ['input' => 1.00, 'output' => 5.00],
    ];

    /** The model used when none has been chosen in the plugin's settings. */
    private const DEFAULT_MODEL = 'claude-opus-5-5';

    /**
     * Models that answer without thinking first. Every other model thinks before it answers and
     * max_tokens caps the thinking and the answer together, so those get THINKING_ROOM on top.
     */
    private const NO_THINKING = ['claude-haiku-4-5'];

    /** Tokens left for thinking, on top of the answer the caller asked for. */
    private const THINKING_ROOM = 6000;

    /**
     * Models whose safety classifiers can decline a request and that can hand a declined request
     * to another model on the server.
     */
    private const FALLBACK_MODELS = ['claude-fable-5-1', 'claude-opus-5-5', 'claude-opus-5', 'claude-sonnet-5-5'];

    /** Beta header for the "default" form of the fallbacks parameter. */
    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    /**
     * Whether this connector has what it needs to make a call (an API key).
     */
    public function isConfigured(): bool
    {
        return (string) $this->params->get('api_key', '') !== '';
    }

    /**
     * What each model costs, in USD per million tokens: the built-in list prices with the
     * "Rates" setting laid over them. One line per model there, "model = input, output".
     *
     * @return array<string, array{input: float, output: float}>
     */
    public function rates(): array
    {
        $rates = self::RATES;

        foreach (preg_split('/\R/', (string) $this->params->get('rates', '')) as $line) {
            if (preg_match('/^\s*([a-z0-9._-]+)\s*[=:]\s*\$?([0-9.]+)\s*[,\/ ]\s*\$?([0-9.]+)\s*$/i', $line, $m)) {
                $rates[strtolower($m[1])] = ['input' => (float) $m[2], 'output' => (float) $m[3]];
            }
        }

        return $rates;
    }

    /**
     * The model a call runs on: a caller's own 'model', else the "Model" setting.
     */
    public function modelFor(array $options = []): string
    {
        $model = trim((string) ($options['model'] ?? ''));

        if ($model === '') {
            $model = trim((string) $this->params->get('model', self::DEFAULT_MODEL));
        }

        return $model !== '' ? $model : self::DEFAULT_MODEL;
    }

    /**
     * The request a call sends, without the API key: the URL, the extra headers and the body.
     * max_tokens is the answer the caller asked for, with room for thinking added for the models
     * that think first.
     *
     * @return array{url: string, headers: array<string, string>, body: array}
     */
    public function request(array $messages, array $options = []): array
    {
        $model     = $this->modelFor($options);
        $maxTokens = (int) ($options['max_tokens'] ?? $this->params->get('max_tokens', 2048));
        $baseUrl   = rtrim((string) ($options['base_url'] ?? $this->params->get('base_url', '')), '/');
        $system    = isset($options['system']) ? (string) $options['system'] : '';

        if ($maxTokens <= 0) {
            $maxTokens = 2048;
        }

        if (!\in_array($model, self::NO_THINKING, true)) {
            $maxTokens += self::THINKING_ROOM;
        }

        $body = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'messages'   => $this->normalizeMessages($messages),
        ];

        if ($system !== '') {
            $body['system'] = $system;
        }

        $headers = [
            'anthropic-version' => self::ANTHROPIC_VERSION,
            'content-type'      => 'application/json',
        ];

        if (\in_array($model, self::FALLBACK_MODELS, true)) {
            $body['fallbacks']         = 'default';
            $headers['anthropic-beta'] = self::FALLBACK_BETA;
        }

        return [
            'url'     => ($baseUrl !== '' ? $baseUrl : self::DEFAULT_BASE_URL) . '/v1/messages',
            'headers' => $headers,
            'body'    => $body,
        ];
    }

    /**
     * Run a chat completion.
     *
     * @param array $messages  [['role' => 'user'|'assistant', 'content' => ...], ...]. A message's content is
     *                         either a string or an array of Messages API content blocks (text and
     *                         image blocks, the latter with a base64 source); it is passed through as
     *                         given, so callers can send images without a connector change.
     * @param array $options    api_key, model, max_tokens, base_url, system
     *
     * @return array  ['ok'=>bool,'text'=>string,'stop_reason'=>?string,
     *                 'usage'=>array,'model'=>?string,'error'=>?string,'raw'=>array]
     */
    public function chat(array $messages, array $options = []): array
    {
        $apiKey = (string) ($options['api_key'] ?? $this->params->get('api_key', ''));

        if ($apiKey === '') {
            return $this->error('Missing Anthropic API key.');
        }

        $request = $this->request($messages, $options);
        $model   = $request['body']['model'];

        try {
            $http = (new HttpFactory())->getHttp();
            // Model calls can be slow; give it a generous timeout (seconds).
            $response = $http->post(
                $request['url'],
                json_encode($request['body']),
                ['x-api-key' => $apiKey] + $request['headers'],
                120
            );
        } catch (\Throwable $e) {
            return $this->error('Could not reach the AI provider: ' . $e->getMessage());
        }

        $decoded = json_decode((string) $response->body, true);

        if (!\is_array($decoded)) {
            return $this->error('The AI provider returned an unreadable response (HTTP ' . $response->code . ').');
        }

        if ((int) $response->code !== 200) {
            $message = $decoded['error']['message'] ?? ('HTTP ' . $response->code);
            return $this->error('AI provider error: ' . $message, $decoded);
        }

        $stopReason = $decoded['stop_reason'] ?? null;

        $usage = [
            'input'  => $decoded['usage']['input_tokens'] ?? null,
            'output' => $decoded['usage']['output_tokens'] ?? null,
        ];

        // A safety refusal comes back HTTP 200 with stop_reason "refusal". Its tokens are
        // billed like any other reply's, so the usage goes back with the error.
        if ($stopReason === 'refusal') {
            return ['usage' => $usage, 'model' => $decoded['model'] ?? $model]
                + $this->error('The AI declined to answer this request.', $decoded, $stopReason);
        }

        return [
            'ok'          => true,
            'text'        => $this->extractText($decoded),
            'stop_reason' => $stopReason,
            'usage'       => $usage,
            'model'       => $decoded['model'] ?? $model,
            'error'       => null,
            'raw'         => $decoded,
        ];
    }

    /**
     * Concatenate the text content blocks from a Messages API response.
     */
    private function extractText(array $decoded): string
    {
        $parts = [];

        foreach ($decoded['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
                $parts[] = $block['text'];
            }
        }

        return trim(implode('', $parts));
    }

    /**
     * Coerce caller messages into the {role, content} shape the API expects.
     */
    private function normalizeMessages(array $messages): array
    {
        $out = [];

        foreach ($messages as $m) {
            if (!isset($m['content'])) {
                continue;
            }

            $role = ($m['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $out[] = ['role' => $role, 'content' => $m['content']];
        }

        return $out;
    }

    private function error(string $message, array $raw = [], ?string $stopReason = null): array
    {
        return [
            'ok'          => false,
            'text'        => '',
            'stop_reason' => $stopReason,
            'usage'       => [],
            'error'       => $message,
            'raw'         => $raw,
        ];
    }
}
