<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;


class MothershipAdminAiUsageCest
{
    private $clientData;

    const EXPENSES_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=expenses";
    const AIUSAGE_URL = "/administrator/index.php?option=com_mothership&view=aiusage";

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
            'email' => 'test.client@mailinator.com',
            'owner_user_id' => 1,
        ]);

        $I->loginAsAdmin();
    }

    /**
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipAiUsageIsReachedFromExpenses(AcceptanceTester $I)
    {
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $I->click("AI Usage", "#toolbar");
        $I->waitForText("Mothership: AI Usage", 30, "h1.page-title");
        $I->seeInCurrentUrl(self::AIUSAGE_URL);

        // Nothing recorded yet.
        $I->see("This month so far", "#aiUsageSummary");
        $I->see("Last month", "#aiUsageSummary");
        $I->see("All time", "#aiUsageSummary");
        $I->see("$0.00", "#aiUsageSummary");
        $I->see("0 calls", "#aiUsageSummary");
        $I->see("Costs are estimates");
        $I->see("No Matching Results");

        $I->click("Expenses", "#toolbar");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
    }

    /**
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipViewAiUsage(AcceptanceTester $I)
    {
        $I->createMothershipAiUsage([
            'feature' => 'expense.read', 'model' => 'claude-opus-5-5', 'client_id' => $this->clientData['id'],
            'input_tokens' => 12000, 'output_tokens' => 800, 'cost' => 0.064,
        ]);
        $I->createMothershipAiUsage(['feature' => 'connection.test', 'input_tokens' => 20, 'output_tokens' => 4, 'cost' => 0.00004]);
        // A model with no rate has no cost.
        $I->createMothershipAiUsage(['feature' => 'other', 'model' => 'unpriced-model', 'cost' => null, 'created' => '2020-01-15 12:00:00']);

        $I->amOnPage(self::AIUSAGE_URL);
        $I->waitForText("Mothership: AI Usage", 30, "h1.page-title");

        $I->takeFullPageScreenshot("mothership-view-aiusage");
        $I->dontSee("Warning");

        $I->see("When", "#aiUsageList thead tr th:nth-child(1)");
        $I->see("Feature", "#aiUsageList thead tr th:nth-child(2)");
        $I->see("About", "#aiUsageList thead tr th:nth-child(3)");
        $I->see("Client", "#aiUsageList thead tr th:nth-child(4)");
        $I->see("Model", "#aiUsageList thead tr th:nth-child(5)");
        $I->see("Input tokens", "#aiUsageList thead tr th:nth-child(6)");
        $I->see("Output tokens", "#aiUsageList thead tr th:nth-child(7)");
        $I->see("Est. cost", "#aiUsageList thead tr th:nth-child(8)");

        $I->seeNumberOfElements("#aiUsageList tbody tr", 3);
        $I->see("Expense from PDF", "#aiUsageList tbody");
        $I->see("Test Client", "#aiUsageList tbody");
        $I->see("claude-opus-5-5", "#aiUsageList tbody");
        $I->see("12,000", "#aiUsageList tbody");
        $I->see("$0.0640", "#aiUsageList tbody");
        $I->see("Connection test", "#aiUsageList tbody");
        $I->see("Unassigned", "#aiUsageList tbody");
        $I->see("unpriced-model", "#aiUsageList tbody");

        $I->see("$0.0640", "#aiUsageList tfoot");
        $I->see("3 calls", "#aiUsageSummary");
        $I->see("2 calls", "#aiUsageSummary");
        $I->see("$0.06", "#aiUsageSummary");

        $I->amOnPage(self::AIUSAGE_URL . "&filter[feature]=expense.read");
        $I->waitForText("Mothership: AI Usage", 30, "h1.page-title");
        $I->seeNumberOfElements("#aiUsageList tbody tr", 1);
        $I->see("Expense from PDF", "#aiUsageList tbody");

        $I->amOnPage(self::AIUSAGE_URL . "&filter[feature]=&filter[client_id]={$this->clientData['id']}");
        $I->waitForText("Mothership: AI Usage", 30, "h1.page-title");
        $I->seeNumberOfElements("#aiUsageList tbody tr", 1);

        $I->amOnPage(self::AIUSAGE_URL . "&filter[client_id]=&filter[search]=unpriced");
        $I->waitForText("Mothership: AI Usage", 30, "h1.page-title");
        $I->seeNumberOfElements("#aiUsageList tbody tr", 1);
        $I->see("unpriced-model", "#aiUsageList tbody");
    }
}
