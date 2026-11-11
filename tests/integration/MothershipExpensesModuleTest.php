<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Module\MothershipExpenses\Administrator\Helper\MothershipExpensesHelper;

/**
 * The numbers behind mod_mothership_expenses, the dashboard's expenses chart.
 */
class MothershipExpensesModuleTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/modules/mod_mothership_expenses/src/Helper/MothershipExpensesHelper.php';
    }

    public function testMonthlyTotalsAreZeroWithoutExpenses()
    {
        $helper = new MothershipExpensesHelper();

        $this->assertSame(array_fill(1, 12, 0.0), $helper->getMonthlyTotals(2026));
        $this->assertSame(0.0, $helper->getTotalToDate());
    }

    public function testMonthlyTotalsSumByMonthOfTheExpenseDate()
    {
        $client = $this->tester->createMothershipClient([
            'name' => 'Test Client',
            'email' => 'test.smith@mailinator.com',
            'owner_user_id' => 1,
        ]);

        // Assigned or not, billable or not: every expense is money out.
        $this->tester->createMothershipExpense(['expense_date' => '2026-01-05', 'amount' => 10.25]);
        $this->tester->createMothershipExpense(['expense_date' => '2026-01-31', 'amount' => 20, 'client_id' => $client['id'], 'billable' => 1]);
        $this->tester->createMothershipExpense(['expense_date' => '2026-03-15', 'amount' => 99.99, 'reimbursed' => 1]);
        $this->tester->createMothershipExpense(['expense_date' => '2026-12-31', 'amount' => 5]);
        // Another year, and an expense with no date: in the total to date only.
        $this->tester->createMothershipExpense(['expense_date' => '2025-12-31', 'amount' => 1000]);
        $this->tester->createMothershipExpense(['expense_date' => null, 'amount' => 7]);

        $helper  = new MothershipExpensesHelper();
        $monthly = $helper->getMonthlyTotals(2026);

        $this->assertSame(range(1, 12), array_keys($monthly));
        $this->assertEqualsWithDelta(30.25, $monthly[1], 0.001);
        $this->assertSame(0.0, $monthly[2]);
        $this->assertEqualsWithDelta(99.99, $monthly[3], 0.001);
        $this->assertEqualsWithDelta(5.0, $monthly[12], 0.001);
        $this->assertEqualsWithDelta(135.24, array_sum($monthly), 0.001);

        $this->assertEqualsWithDelta(1000.0, array_sum($helper->getMonthlyTotals(2025)), 0.001);
        $this->assertEqualsWithDelta(1142.24, $helper->getTotalToDate(), 0.001);
    }
}
