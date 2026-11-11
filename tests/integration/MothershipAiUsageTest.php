<?php

namespace Tests\Integration;

use Joomla\CMS\Factory;
use Tests\Support\MothershipComponentTest;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiResult;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiUsage;

/**
 * What the AI features cost: pricing a call, recording it against a client, the totals and
 * the list. None of them reach the network. Each test uses a provider name of its
 * own, so rows left by another test do not count.
 */
class MothershipAiUsageTest extends MothershipComponentTest
{
    private const RATES = ['claude-opus-5' => ['input' => 5.0, 'output' => 25.0], 'claude-haiku-4-5' => ['input' => 1.0, 'output' => 5.0]];

    private string $provider;

    protected function _before()
    {
        parent::_before();
        Factory::getApplication()->getLanguage()->load('com_mothership', JPATH_ADMINISTRATOR);

        $this->provider = 'test' . substr(md5(uniqid('', true)), 0, 12);
    }

    private function reply(int $input, int $output, ?string $model = 'claude-opus-5'): AiResult
    {
        return AiResult::fromArray(['ok' => true, 'text' => 'ok', 'usage' => ['input' => $input, 'output' => $output], 'model' => $model]);
    }

    /** A stored call, as record() would leave it, at a time of the test's choosing. */
    private function call(string $created, ?float $cost, array $row = []): int
    {
        return (int) $this->tester->haveInDatabase('jos_mothership_ai_usage', array_merge([
            'provider' => $this->provider, 'model' => 'claude-opus-5', 'feature' => 'expense.read',
            'input_tokens' => 1000, 'output_tokens' => 200, 'cost' => $cost, 'created' => $created, 'created_by' => 0,
        ], $row));
    }

    public function testACallIsPricedFromTheModelsRate()
    {
        $this->assertSame(0.01, AiUsage::cost(['input' => 1000, 'output' => 200], self::RATES['claude-opus-5']));
        $this->assertSame(0.000006, AiUsage::cost(['input' => 1, 'output' => 1], self::RATES['claude-haiku-4-5']));
        $this->assertNull(AiUsage::cost(['input' => 1000, 'output' => 200], null), 'no rate, no cost');

        $this->assertSame(self::RATES['claude-opus-5'], AiUsage::rateFor('claude-opus-5', self::RATES));
        $this->assertSame(self::RATES['claude-haiku-4-5'], AiUsage::rateFor('claude-haiku-4-5-20251001', self::RATES), 'a dated snapshot is priced as its family');
        $this->assertNull(AiUsage::rateFor('claude-opus-55', self::RATES), 'a family is matched at a dash, not mid-word');
        $this->assertNull(AiUsage::rateFor('gpt-9', self::RATES));
        $this->assertNull(AiUsage::rateFor('', self::RATES));
    }

    public function testTheLongestMatchingFamilyWins()
    {
        $rates = ['claude-opus-5' => ['input' => 5.0, 'output' => 25.0], 'claude-opus-5-5' => ['input' => 4.0, 'output' => 20.0]];

        $this->assertSame($rates['claude-opus-5-5'], AiUsage::rateFor('claude-opus-5-5-20260901', $rates));
        $this->assertSame($rates['claude-opus-5-5'], AiUsage::rateFor('claude-opus-5-5', $rates));
        $this->assertSame($rates['claude-opus-5'], AiUsage::rateFor('claude-opus-5', $rates));
    }

    public function testACallIsRecordedAgainstTheClientOfWhatItWasAbout()
    {
        $client  = $this->tester->createMothershipClient(['name' => 'Usage ' . $this->provider, 'owner_user_id' => 1]);
        $account = $this->tester->createMothershipAccount(['client_id' => $client['id'], 'name' => 'Usage Account']);
        $project = $this->tester->createMothershipProject(['client_id' => $client['id'], 'account_id' => $account['id'], 'name' => 'Usage Project']);
        $invoice = $this->tester->createMothershipInvoice(['client_id' => $client['id'], 'account_id' => $account['id'], 'project_id' => $project['id']]);

        $id = AiUsage::record($this->provider, $this->reply(1000, 200), [
            'feature' => 'expense.read', 'resource_type' => 'invoice', 'resource_id' => $invoice['id'],
        ], self::RATES);

        $this->assertGreaterThan(0, $id);
        $this->tester->seeInDatabase('jos_mothership_ai_usage', [
            'id' => $id, 'provider' => $this->provider, 'model' => 'claude-opus-5', 'feature' => 'expense.read',
            'resource_type' => 'invoice', 'resource_id' => $invoice['id'],
            'client_id' => $client['id'], 'account_id' => $account['id'], 'project_id' => $project['id'],
            'input_tokens' => 1000, 'output_tokens' => 200, 'cost' => '0.010000',
        ]);
    }

    public function testWhatTheCallerSaysWinsAndUnknownsAreFiledPlainly()
    {
        $id = AiUsage::record($this->provider, $this->reply(500, 50, 'some-new-model'), [
            'feature' => 'made.up', 'client_id' => 3, 'account_id' => 0,
        ], self::RATES);

        $this->tester->seeInDatabase('jos_mothership_ai_usage', [
            'id' => $id, 'feature' => 'other', 'resource_type' => null, 'resource_id' => null,
            'client_id' => 3, 'account_id' => null, 'cost' => null,
        ]);
    }

    public function testACallThatReportedNoTokensIsNotRecorded()
    {
        $this->assertSame(0, AiUsage::record($this->provider, AiResult::fail('Could not reach the AI provider.'), ['feature' => 'expense.read'], self::RATES));
        $this->assertSame(0, AiUsage::record($this->provider, $this->reply(0, 0), [], self::RATES));
        $this->tester->dontSeeInDatabase('jos_mothership_ai_usage', ['provider' => $this->provider]);
    }

    public function testRecordingACallWritesNoExpense()
    {
        $before = $this->tester->grabNumRecords('jos_mothership_expenses');

        AiUsage::record($this->provider, $this->reply(200000, 40000), ['feature' => 'expense.read'], self::RATES);

        $this->assertSame($before, $this->tester->grabNumRecords('jos_mothership_expenses'), 'the log is for watching the spend; the bill is entered by hand');
    }

    public function testTheSummaryCountsTheMonthsApart()
    {
        $before = AiUsage::summary('2026-10-10');

        $this->call('2026-10-05 12:00:00', 1.00);
        $this->call('2026-09-15 12:00:00', 2.00);
        $this->call('2026-06-15 12:00:00', 4.00);

        $after = AiUsage::summary('2026-10-10');

        $this->assertEqualsWithDelta(1.00, $after['this_month']['cost'] - $before['this_month']['cost'], 0.00001);
        $this->assertEqualsWithDelta(2.00, $after['last_month']['cost'] - $before['last_month']['cost'], 0.00001);
        $this->assertEqualsWithDelta(7.00, $after['all_time']['cost'] - $before['all_time']['cost'], 0.00001);
        $this->assertSame(['this_month', 'last_month', 'all_time'], array_keys($after));
        $this->assertSame(3, $after['all_time']['calls'] - $before['all_time']['calls']);
    }

    public function testTheUsageListFiltersByFeature()
    {
        $read  = $this->call('2026-08-10 12:00:00', 1.25, ['model' => 'm-' . $this->provider]);
        $test  = $this->call('2026-08-11 12:00:00', 0.50, ['model' => 'm-' . $this->provider, 'feature' => 'connection.test']);

        $ids = function (array $state) {
            $model = $this->adminModel('Aiusage');
            $model->setState('filter.search', 'm-' . $this->provider);
            $model->setState('list.limit', 0);
            foreach ($state as $key => $value) {
                $model->setState($key, $value);
            }

            return array_map(fn ($row) => (int) $row->id, $model->getItems());
        };

        $this->assertSame([$test, $read], $ids([]), 'newest first');
        $this->assertSame([$test], $ids(['filter.feature' => 'connection.test']));
        $this->assertSame([$read], $ids(['filter.feature' => 'expense.read']));
        $this->assertNotNull($this->adminModel('Aiusage')->getFilterForm(), 'the filter form loads');
    }

    public function testAFeatureHasALabel()
    {
        $this->assertSame('Expense from PDF', AiUsage::featureLabel('expense.read'));
        $this->assertSame('Connection test', AiUsage::featureLabel('connection.test'));
        $this->assertSame('made.up', AiUsage::featureLabel('made.up'), 'an unknown feature is shown as it is');
    }
}
