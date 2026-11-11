<?php

namespace TrevorBice\Component\Mothership\Administrator\Service\Ai;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Provider-agnostic entry point for AI features in Mothership.
 *
 * The component holds only the two generic settings (Options -> AI): whether AI
 * is on (ai_enabled) and which provider to use (ai_provider). Everything provider
 * specific (API key, model, token ceiling, base URL) lives in that provider's
 * own connector plugin (group "mothership-ai"), the same way each payment method
 * plugin owns its own credentials. The actual call is made by the selected
 * connector. To add another AI, drop in a new plugin (e.g. plg_mothershipai_openai)
 * with its own settings and add its value to the ai_provider list in config.xml.
 * No caller changes.
 *
 * Switching providers just changes ai_provider: each connector keeps its own
 * saved credentials, so nothing is re-entered and nothing is clobbered.
 *
 * Callers should stick to this class:
 *
 *   if (AiService::isEnabled()) {
 *       $result = AiService::text('Write a one-line summary of: ' . $notes);
 *       if ($result->ok) { echo $result->text; }
 *   }
 *
 * A connector plugin must expose:
 *   public function chat(array $messages, array $options = []): array
 * returning ['ok'=>bool,'text'=>string,'stop_reason'=>?string,'usage'=>array,'error'=>?string,'raw'=>array].
 */
class AiService
{
    /**
     * Whether AI features are switched on and the selected provider is ready.
     *
     * "Ready" means the provider's connector plugin is enabled and reports
     * itself configured (e.g. has an API key). Use this to gate AI buttons.
     */
    public static function isEnabled(): bool
    {
        $params = ComponentHelper::getParams('com_mothership');

        if (!(int) $params->get('ai_enabled', 0)) {
            return false;
        }

        try {
            $connector = self::getConnector(self::providerName());
        } catch (\RuntimeException $e) {
            return false;
        }

        // A connector may expose isConfigured() to say whether it has what it
        // needs (key, etc.). If it doesn't, assume enabled == ready.
        if (method_exists($connector, 'isConfigured')) {
            return (bool) $connector->isConfigured();
        }

        return true;
    }

    /**
     * The configured provider key (e.g. "claude").
     */
    public static function providerName(): string
    {
        return strtolower((string) ComponentHelper::getParams('com_mothership')->get('ai_provider', 'claude'));
    }

    /**
     * Convenience: send a single user prompt, get an AiResult back.
     *
     * @param string $prompt   The user message.
     * @param array  $options  Optional overrides: 'system', 'model', 'max_tokens'.
     */
    public static function text(string $prompt, array $options = []): AiResult
    {
        return self::chat([['role' => 'user', 'content' => $prompt]], $options);
    }

    /**
     * Run a full multi-message conversation through the selected connector.
     *
     * @param array $messages  [['role' => 'user'|'assistant', 'content' => ...], ...]. Content is a string, or an
     *                         array of provider content blocks (text and base64 image blocks) when a
     *                         turn carries images; the connector passes it through as given.
     * @param array $options   'system' (string), 'model' (string), 'max_tokens' (int).
     */
    public static function chat(array $messages, array $options = []): AiResult
    {
        $params = ComponentHelper::getParams('com_mothership');

        if (!(int) $params->get('ai_enabled', 0)) {
            return AiResult::fail('AI features are disabled in the Mothership settings.');
        }

        try {
            $plugin = self::getConnector(self::providerName());
        } catch (\RuntimeException $e) {
            return AiResult::fail($e->getMessage());
        }

        // Credentials/model/base URL come from the connector's own settings.
        // $options is passed straight through for per-call overrides only
        // (e.g. 'system', or a one-off 'model' / 'max_tokens').

        try {
            $raw = $plugin->chat($messages, $options);
        } catch (\Throwable $e) {
            return AiResult::fail('AI connector error: ' . $e->getMessage());
        }

        if (!\is_array($raw)) {
            return AiResult::fail('The AI connector returned an unexpected response.');
        }

        return AiResult::fromArray($raw);
    }

    /**
     * Run a minimal live call to verify a provider's connector is configured
     * and reachable. Bypasses the ai_enabled master switch so the connection
     * can be checked before AI features are switched on. Uses a tiny prompt and
     * token cap so the cost is negligible.
     *
     * @param string $provider  Provider key; defaults to the configured one.
     */
    public static function testConnection(string $provider = '', array $options = []): AiResult
    {
        $provider = $provider !== '' ? strtolower($provider) : self::providerName();

        try {
            $connector = self::getConnector($provider);
        } catch (\RuntimeException $e) {
            return AiResult::fail($e->getMessage());
        }

        $options = array_merge(['max_tokens' => 16], $options);

        try {
            $raw = $connector->chat(
                [['role' => 'user', 'content' => 'Reply with the single word: OK.']],
                $options
            );
        } catch (\Throwable $e) {
            return AiResult::fail('AI connector error: ' . $e->getMessage());
        }

        if (!\is_array($raw)) {
            return AiResult::fail('The AI connector returned an unexpected response.');
        }

        return AiResult::fromArray($raw);
    }

    /**
     * Loads and instantiates the connector plugin for the given provider.
     *
     * Mirrors the mothership-invoice-pdf plugin-selection pattern: import the
     * group, find the enabled plugin whose name matches, build its class.
     *
     * @throws \RuntimeException when the provider plugin is missing or disabled.
     */
    protected static function getConnector(string $provider)
    {
        $normalized = strtolower($provider);

        PluginHelper::importPlugin('mothership-ai');

        foreach (PluginHelper::getPlugin('mothership-ai') as $plugin) {
            if ($plugin->name !== $normalized) {
                continue;
            }

            // e.g. "claude" -> PlgMothershipAiClaude
            $className = 'PlgMothershipAi' . ucfirst($plugin->name);

            if (!class_exists($className)) {
                throw new \RuntimeException("AI connector class '$className' not found.");
            }

            $dispatcher = Factory::getApplication()->getDispatcher();
            $instance   = new $className($dispatcher, (array) $plugin);

            if (!method_exists($instance, 'chat')) {
                throw new \RuntimeException("AI connector '$provider' does not implement chat().");
            }

            return $instance;
        }

        throw new \RuntimeException("AI connector '$provider' is not installed or not enabled.");
    }
}
