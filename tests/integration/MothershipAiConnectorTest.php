<?php

namespace Tests\Integration;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiResult;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiService;

/**
 * The AI connector: the service's answers when AI is off or the connector is missing, and the
 * request the Claude plugin builds. Nothing here reaches a provider.
 */
class MothershipAiConnectorTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/Ai/AiResult.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/Ai/AiService.php';
        require_once JPATH_ROOT . '/plugins/mothership-ai/claude/claude.php';

        // As installed: the switch off and the connector disabled, whatever ran before.
        $this->tester->haveMothershipAi(false);
    }

    private function claude(array $params = []): \PlgMothershipAiClaude
    {
        $dispatcher = Factory::getApplication()->getDispatcher();

        return new \PlgMothershipAiClaude($dispatcher, [
            'type'   => 'mothership-ai',
            'name'   => 'claude',
            'params' => new Registry($params),
        ]);
    }

    public function testAiIsOffUntilItIsSwitchedOn()
    {
        $this->assertFalse(AiService::isEnabled());

        $result = AiService::text('Hello');

        $this->assertFalse($result->ok);
        $this->assertSame('AI features are disabled in the Mothership settings.', $result->error);
        $this->assertSame('', $result->text);
    }

    public function testAMissingConnectorIsAnErrorNotAnException()
    {
        $result = AiService::testConnection('nosuchprovider');

        $this->assertFalse($result->ok);
        $this->assertSame("AI connector 'nosuchprovider' is not installed or not enabled.", $result->error);
    }

    public function testAResultIsBuiltFromWhatAConnectorReturns()
    {
        $result = AiResult::fromArray([
            'ok' => true, 'text' => 'Hi', 'stop_reason' => 'end_turn',
            'usage' => ['input' => 3, 'output' => 5], 'raw' => ['model' => 'claude-haiku-4-5'],
        ]);

        $this->assertTrue($result->ok);
        $this->assertSame('Hi', (string) $result);
        $this->assertSame('end_turn', $result->stopReason);
        $this->assertSame(['input' => 3, 'output' => 5], $result->usage);
        $this->assertSame('claude-haiku-4-5', $result->model, 'the model falls back to the raw response');

        $failed = AiResult::fail('No.');
        $this->assertFalse($failed->ok);
        $this->assertSame('No.', $failed->error);
    }

    public function testClaudeNeedsAKeyBeforeItSendsAnything()
    {
        $this->assertFalse($this->claude()->isConfigured());
        $this->assertTrue($this->claude(['api_key' => 'not-a-real-key'])->isConfigured());

        $reply = $this->claude()->chat([['role' => 'user', 'content' => 'Hello']]);

        $this->assertFalse($reply['ok']);
        $this->assertSame('Missing Anthropic API key.', $reply['error']);
    }

    public function testClaudeBuildsAMessagesApiRequest()
    {
        $request = $this->claude(['model' => 'claude-haiku-4-5', 'max_tokens' => 1024])->request(
            [['role' => 'user', 'content' => 'Hello'], ['role' => 'assistant', 'content' => 'Hi'], ['role' => 'system', 'content' => 'x'], ['role' => 'user']],
            ['system' => 'Be brief.']
        );

        $this->assertSame('https://api.anthropic.com/v1/messages', $request['url']);
        $this->assertSame('claude-haiku-4-5', $request['body']['model']);
        $this->assertSame(1024, $request['body']['max_tokens'], 'a model that does not think gets what was asked for');
        $this->assertSame('Be brief.', $request['body']['system']);
        $this->assertSame(
            [['role' => 'user', 'content' => 'Hello'], ['role' => 'assistant', 'content' => 'Hi'], ['role' => 'user', 'content' => 'x']],
            $request['body']['messages'],
            'an unknown role is sent as the user, and a message with no content is dropped'
        );
        $this->assertArrayNotHasKey('x-api-key', $request['headers'], 'the key is added only when the call is made');
        $this->assertArrayNotHasKey('fallbacks', $request['body']);
    }

    public function testClaudeLeavesRoomForThinkingAndHonoursOverrides()
    {
        $plugin = $this->claude(['model' => 'claude-opus-5-5', 'max_tokens' => 2048, 'base_url' => 'https://gateway.example.test/']);

        $request = $plugin->request([['role' => 'user', 'content' => 'Hello']]);
        $this->assertSame('https://gateway.example.test/v1/messages', $request['url']);
        $this->assertSame(2048 + 6000, $request['body']['max_tokens']);
        $this->assertSame('default', $request['body']['fallbacks']);
        $this->assertArrayHasKey('anthropic-beta', $request['headers']);

        $request = $plugin->request([['role' => 'user', 'content' => 'Hello']], ['model' => 'claude-haiku-4-5', 'max_tokens' => 16]);
        $this->assertSame('claude-haiku-4-5', $request['body']['model'], "a caller's own model wins");
        $this->assertSame(16, $request['body']['max_tokens']);

        $this->assertSame('claude-opus-5-5', $this->claude()->modelFor(), 'the default model');
    }

    public function testClaudeRatesCanBeOverriddenInItsSettings()
    {
        $rates = $this->claude()->rates();
        $this->assertSame(['input' => 4.0, 'output' => 20.0], $rates['claude-opus-5-5']);

        $rates = $this->claude(['rates' => "claude-opus-5-5 = 3.50, 17.50\nnot a rate\nnew-model: \$1 / \$2"])->rates();
        $this->assertSame(['input' => 3.5, 'output' => 17.5], $rates['claude-opus-5-5']);
        $this->assertSame(['input' => 1.0, 'output' => 2.0], $rates['new-model']);
        $this->assertSame(['input' => 2.0, 'output' => 10.0], $rates['claude-sonnet-5-5'], 'the others keep their list price');
    }
}
