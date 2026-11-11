<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;


class MothershipAdminExpensesCest
{
    private $clientData;
    private $accountData;
    private $projectData;
    private $otherClientData;
    private $otherAccountData;
    private $expenseData;
    private $unassignedExpenseData;

    const EXPENSES_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=expenses";
    const ADMIN_TIMEZONE = "America/Los_Angeles";
    const EXPENSE_EDIT_URL = "/administrator/index.php?option=com_mothership&view=expense&layout=edit&id=%s";

    public function _before(AcceptanceTester $I)
    {
        $I->resetMothershipTables();

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
            'email' => 'test.client@mailinator.com',
            'phone' => '(123) 456-7890',
            'owner_user_id' => 1,
        ]);

        $this->otherClientData = $I->createMothershipClient([
            'name' => 'Acme Inc.',
            'email' => 'test.acme.inc@mailinator.com',
            'owner_user_id' => 1,
        ]);

        $this->otherAccountData = $I->createMothershipAccount([
            'client_id' => $this->otherClientData['id'],
            'name' => 'Roadrunner Products',
        ]);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->projectData = $I->createMothershipProject([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'Test Project',
        ]);

        // One expense assigned all the way down to a project, one assigned to nothing.
        $this->expenseData = $I->createMothershipExpense([
            'item' => 'Stock Photos',
            'merchant' => 'Photo Shop',
            'category' => 'ADVERTISING',
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => $this->projectData['id'],
            'expense_date' => '2026-03-15',
            'amount' => 49.5,
            'billable' => 1,
            'payment_account' => 'Business Visa',
        ]);

        $this->unassignedExpenseData = $I->createMothershipExpense([
            'item' => 'Code Editor',
            'merchant' => 'Editor Co',
            'category' => 'SOFTWARE',
            'expense_date' => '2026-03-10',
            'amount' => 1200,
        ]);

        // An administrator west of UTC: a date must show as stored, not as the evening before.
        $I->updateInDatabase('jos_users', ['params' => '{"timezone":"' . self::ADMIN_TIMEZONE . '"}'], ['username' => 'admin']);

        $I->loginAsAdmin();
    }

    /**
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipViewAllExpenses(AcceptanceTester $I)
    {
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $I->takeFullPageScreenshot("mothership-view-expenses");

        $I->dontSee("Warning");

        $toolbar = "#toolbar";
        $toolbarNew = "#toolbar-new";
        $I->seeElement("{$toolbar} {$toolbarNew}");
        $I->see("New", "{$toolbar} {$toolbarNew} .btn.button-new");

        $I->seeElement("#j-main-container ");
        $I->seeElement("#j-main-container thead");

        $I->see("ID", "#j-main-container table thead tr th:nth-child(2)");
        $I->see("Date", "#j-main-container table thead tr th:nth-child(3)");
        $I->see("Item", "#j-main-container table thead tr th:nth-child(4)");
        $I->see("Category", "#j-main-container table thead tr th:nth-child(5)");
        $I->see("Merchant", "#j-main-container table thead tr th:nth-child(6)");
        $I->see("Paid From", "#j-main-container table thead tr th:nth-child(7)");
        $I->see("Assignment", "#j-main-container table thead tr th:nth-child(8)");
        $I->see("Billable", "#j-main-container table thead tr th:nth-child(9)");
        $I->see("Amount", "#j-main-container table thead tr th:nth-child(10)");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        // Newest first: the assigned expense, then the unassigned one.
        $row = "#j-main-container table.itemList tbody tr:nth-child(1)";
        $I->see("{$this->expenseData['id']}", "{$row} td:nth-child(2)");
        $I->see("2026-03-15", "{$row} td:nth-child(3)");
        $I->see("Stock Photos", "{$row} td:nth-child(4)");
        $I->see("Advertising", "{$row} td:nth-child(5)");
        $I->see("Photo Shop", "{$row} td:nth-child(6)");
        $I->see("Business Visa", "{$row} td:nth-child(7)");
        $I->see("Test Client", "{$row} td:nth-child(8)");
        $I->see("Test Account › Test Project", "{$row} td:nth-child(8)");
        $I->see("Yes", "{$row} td:nth-child(9)");
        $I->see("$49.50", "{$row} td:nth-child(10)");

        $row = "#j-main-container table.itemList tbody tr:nth-child(2)";
        $I->see("{$this->unassignedExpenseData['id']}", "{$row} td:nth-child(2)");
        $I->see("Code Editor", "{$row} td:nth-child(4)");
        $I->see("Software", "{$row} td:nth-child(5)");
        $I->see("Unassigned", "{$row} td:nth-child(8)");
        $I->dontSee("Yes", "{$row} td:nth-child(9)");
        $I->see("$1,200.00", "{$row} td:nth-child(10)");

        $I->see("Page total", "#j-main-container table.itemList tfoot");
        $I->see("$1,249.50", "#j-main-container table.itemList tfoot");

        $I->see("1 - 2 / 2 items", "#j-main-container .pagination__wrapper");
    }

    /**
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipFilterExpenses(AcceptanceTester $I)
    {
        $rows = "#j-main-container table.itemList tbody tr";

        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL . "&filter[category]=SOFTWARE");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->seeNumberOfElements($rows, 1);
        $I->see("Code Editor", $rows);
        $I->dontSee("Stock Photos", $rows);
        // The total is of the rows on the page, not of every expense.
        $I->see("$1,200.00", "#j-main-container table.itemList tfoot");

        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL . "&filter[category]=&filter[billable]=1");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->seeNumberOfElements($rows, 1);
        $I->see("Stock Photos", $rows);

        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL . "&filter[billable]=&filter[client_id]={$this->clientData['id']}&filter[account_id]={$this->accountData['id']}");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->seeNumberOfElements($rows, 1);
        $I->see("Stock Photos", $rows);

        // A client with no expenses.
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL . "&filter[client_id]={$this->otherClientData['id']}&filter[account_id]=");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->seeNumberOfElements($rows, 0);
        $I->see("No Matching Results");

        // Search looks at the item and the merchant.
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL . "&filter[client_id]=&filter[search]=Editor");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->seeNumberOfElements($rows, 1);
        $I->see("Code Editor", $rows);

        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL . "&filter[search]=id:{$this->expenseData['id']}");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->seeNumberOfElements($rows, 1);
        $I->see("Stock Photos", $rows);
    }

    /**
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipAddExpense(AcceptanceTester $I)
    {
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $I->click("#toolbar #toolbar-new .btn.button-new");
        $I->waitForText("Mothership: New Expense", 30, "h1.page-title");

        $I->takeFullPageScreenshot("mothership-add-expense");

        $I->dontSee("Warning");

        $I->see("Save", "#toolbar");
        $I->see("Save & Close", "#toolbar");
        $I->see("Cancel", "#toolbar");

        $I->seeElement("input#jform_item");
        $I->seeElement("input#jform_merchant");
        $I->seeElement("select#jform_category");
        $I->seeElement("input#jform_amount");
        $I->seeElement("input#jform_expense_date");
        $I->seeElement("input#jform_transaction_id");
        $I->seeElement("input#jform_payment_account");
        $I->seeElement("textarea#jform_description");

        // Attempt to save the form without filling out any fields
        $I->click("Save", "#toolbar");
        $I->wait(1);

        $I->see("The form cannot be submitted as it's missing required data.");
        $I->see("Please correct the marked fields and try again.");

        $I->amGoingTo("Fill out the form");

        $I->fillField("input#jform_item", "Hosting Plan");
        $I->fillField("input#jform_merchant", "Host Co");
        $I->selectOption("select#jform_category", "WEBHOST");
        $I->fillField("input#jform_amount", "24.00");
        $I->fillField("input#jform_expense_date", "2026-04-01");
        $I->fillField("input#jform_transaction_id", "ORDER-123");
        $I->fillField("input#jform_payment_account", "Business Visa");
        $I->fillField("textarea#jform_description", "Monthly hosting");

        $I->click("Assignment", "#expenseTab");
        $I->waitForElementVisible("select#jform_client_id", 10);

        // Choosing a client loads its accounts, and only its accounts.
        $I->selectOption("select#jform_client_id", $this->clientData['id']);
        $I->waitForJS("return !document.querySelector(\"select#jform_account_id option[value='{$this->otherAccountData['id']}']\")", 10);
        $I->seeElementInDOM("select#jform_account_id option[value='{$this->accountData['id']}']");

        // Choosing an account loads its projects.
        $I->selectOption("select#jform_account_id", $this->accountData['id']);
        $I->waitForJS("return document.querySelectorAll('select#jform_project_id option').length === 2", 10);
        $I->selectOption("select#jform_project_id", $this->projectData['id']);

        $I->click("label[for=jform_billable1]");

        $I->click("Save & Close", "#toolbar");
        $I->waitForText("Expense Hosting Plan saved.", 10, "#system-message-container .alert-message");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->seeCurrentUrlEquals(self::EXPENSES_VIEW_ALL_URL);

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 3);

        $I->seeInDatabase('jos_mothership_expenses', [
            'item' => 'Hosting Plan',
            'merchant' => 'Host Co',
            'category' => 'WEBHOST',
            'amount' => '24.00',
            'expense_date' => '2026-04-01',
            'transaction_id' => 'ORDER-123',
            'payment_account' => 'Business Visa',
            'description' => 'Monthly hosting',
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => $this->projectData['id'],
            'billable' => 1,
            'reimbursed' => 0,
        ]);
    }

    /**
     * An expense needs no client, account or project.
     *
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipAddUnassignedExpense(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::EXPENSE_EDIT_URL, 0));
        $I->waitForText("Mothership: New Expense", 30, "h1.page-title");

        // A new expense is dated today where the administrator is, which is not always the UTC day.
        $today = (new \DateTimeImmutable('now', new \DateTimeZone(self::ADMIN_TIMEZONE)))->format('Y-m-d');
        $I->seeInField("input#jform_expense_date", $today);

        $I->fillField("input#jform_item", "Office Rent");
        $I->selectOption("select#jform_category", "RENT");
        $I->fillField("input#jform_amount", "800.00");

        $I->click("Save & Close", "#toolbar");
        $I->waitForText("Expense Office Rent saved.", 10, "#system-message-container .alert-message");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $id = $I->grabFromDatabase('jos_mothership_expenses', 'id', ['item' => 'Office Rent']);
        $I->assertSame($today, $I->grabFromDatabase('jos_mothership_expenses', 'expense_date', ['id' => $id]));
        $I->assertNull($I->grabFromDatabase('jos_mothership_expenses', 'client_id', ['id' => $id]));
        $I->assertNull($I->grabFromDatabase('jos_mothership_expenses', 'account_id', ['id' => $id]));
        $I->assertNull($I->grabFromDatabase('jos_mothership_expenses', 'project_id', ['id' => $id]));

        $I->see("Unassigned", "#j-main-container table.itemList tbody");
    }

    /**
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipEditExpense(AcceptanceTester $I)
    {
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $I->click("Stock Photos");
        $I->waitForText("Mothership: Edit Expense", 30, "h1.page-title");

        $I->seeInField("input#jform_item", "Stock Photos");
        $I->seeInField("input#jform_merchant", "Photo Shop");
        $I->seeOptionIsSelected("select#jform_category", "Advertising");
        $I->seeInField("input#jform_amount", "49.50");
        $I->seeInField("input#jform_expense_date", "2026-03-15");

        $I->click("Assignment", "#expenseTab");
        $I->waitForElementVisible("select#jform_client_id", 10);
        $I->seeOptionIsSelected("select#jform_client_id", "Test Client");
        $I->seeOptionIsSelected("select#jform_account_id", "Test Account");
        $I->seeOptionIsSelected("select#jform_project_id", "Test Project");

        $I->click("Details", "#expenseTab");
        $I->waitForElementVisible("input#jform_amount", 10);
        $I->fillField("input#jform_amount", "59.50");

        // Save keeps the expense open.
        $I->click("Save", "#toolbar");
        $I->waitForText("Expense Stock Photos saved.", 10, "#system-message-container .alert-message");
        $I->waitForText("Mothership: Edit Expense", 30, "h1.page-title");
        $I->seeInField("input#jform_amount", "59.50");

        $I->seeInField("input#jform_expense_date", "2026-03-15");

        $I->seeInDatabase('jos_mothership_expenses', [
            'id' => $this->expenseData['id'],
            'expense_date' => '2026-03-15',
            'amount' => '59.50',
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => $this->projectData['id'],
        ]);

        // Changing the client drops the account and project of the old one.
        $I->click("Assignment", "#expenseTab");
        $I->waitForElementVisible("select#jform_client_id", 10);
        $I->selectOption("select#jform_client_id", $this->otherClientData['id']);
        $I->waitForJS("return !document.querySelector(\"select#jform_account_id option[value='{$this->accountData['id']}']\")", 10);
        $I->waitForJS("return !document.querySelector(\"select#jform_project_id option[value='{$this->projectData['id']}']\")", 10);
        $I->seeElementInDOM("select#jform_account_id option[value='{$this->otherAccountData['id']}']");

        $I->click("Save & Close", "#toolbar");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $I->seeInDatabase('jos_mothership_expenses', [
            'id' => $this->expenseData['id'],
            'client_id' => $this->otherClientData['id'],
        ]);
        $I->assertNull($I->grabFromDatabase('jos_mothership_expenses', 'account_id', ['id' => $this->expenseData['id']]));
        $I->assertNull($I->grabFromDatabase('jos_mothership_expenses', 'project_id', ['id' => $this->expenseData['id']]));
    }

    /**
     * A date cleared on the form is dated today instead of failing the save.
     *
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipSaveExpenseWithoutDate(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::EXPENSE_EDIT_URL, $this->expenseData['id']));
        $I->waitForText("Mothership: Edit Expense", 30, "h1.page-title");

        $I->fillField("input#jform_expense_date", "");
        $I->click("Save", "#toolbar");
        $I->waitForText("Expense Stock Photos saved.", 10, "#system-message-container .alert-message");

        $today = (new \DateTimeImmutable('now', new \DateTimeZone(self::ADMIN_TIMEZONE)))->format('Y-m-d');
        $I->seeInField("input#jform_expense_date", $today);
        $I->assertSame($today, $I->grabFromDatabase('jos_mothership_expenses', 'expense_date', ['id' => $this->expenseData['id']]));
    }

    /**
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipCancelExpenseEdit(AcceptanceTester $I)
    {
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $I->click("Code Editor");
        $I->waitForText("Mothership: Edit Expense", 30, "h1.page-title");
        $I->fillField("input#jform_amount", "1.00");
        $I->click("Close", "#toolbar");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->seeCurrentUrlEquals(self::EXPENSES_VIEW_ALL_URL);

        $I->seeInDatabase('jos_mothership_expenses', ['id' => $this->unassignedExpenseData['id'], 'amount' => '1200.00']);
        $I->assertNull($I->grabFromDatabase('jos_mothership_expenses', 'checked_out', ['id' => $this->unassignedExpenseData['id']]));
    }

    /**
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipCancelClientEdit(AcceptanceTester $I)
    {
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->click("Test Client");
        $I->waitForText("Mothership: Edit Client", 30, "h1.page-title");
        $I->click("Close", "#toolbar");
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");
        $I->seeCurrentUrlEquals(self::EXPENSES_VIEW_ALL_URL);
    }

    /**
     * @group backend
     * @group expense
     * @group delete
     * @group backend-expense
     */
    public function MothershipDeleteExpense(AcceptanceTester $I)
    {
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 2);

        $I->click("#j-main-container table.itemList tbody tr:nth-child(1) input[name='cid[]']");
        $I->click("Actions");
        $I->see("Check-in", "joomla-toolbar-button#status-group-children-checkin");
        $I->seeElement("joomla-toolbar-button#status-group-children-checkin", ['task' => "expenses.checkIn"]);
        $I->see("Edit", "joomla-toolbar-button#status-group-children-edit");
        $I->seeElement("joomla-toolbar-button#status-group-children-edit", ['task' => "expense.edit"]);
        $I->see("Delete", "joomla-toolbar-button#status-group-children-delete");
        $I->seeElement("joomla-toolbar-button#status-group-children-delete", ['task' => "expenses.delete"]);

        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->seeInCurrentUrl(self::EXPENSES_VIEW_ALL_URL);
        $I->see("Mothership: Expenses", "h1.page-title");
        $I->see("1 expense(s) deleted.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 1);

        $I->dontSeeInDatabase('jos_mothership_expenses', ['id' => $this->expenseData['id']]);
        $I->seeInDatabase('jos_mothership_expenses', ['id' => $this->unassignedExpenseData['id']]);
    }

    /**
     * @group backend
     * @group expense
     * @group delete
     * @group backend-expense
     */
    public function MothershipDeleteMultipleExpenses(AcceptanceTester $I)
    {
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $I->click("input[name=checkall-toggle]");
        $I->click("Actions");
        $I->click("Delete", "#toolbar");
        $I->wait(1);

        $I->see("2 expense(s) deleted.", ".alert-message");
        $I->seeNumberOfElements("#j-main-container table.itemList tbody tr", 0);
        $I->dontSeeInDatabase('jos_mothership_expenses', []);
    }

    /**
     * @group backend
     * @group expense
     * @group backend-expense
     */
    public function MothershipEditInvalidExpense(AcceptanceTester $I)
    {
        // Leave the dashboard first: its quick icons are still calling home after login, and
        // a request that ends after the redirect below would write the session without the message.
        $I->amOnPage(self::EXPENSES_VIEW_ALL_URL);
        $I->waitForText("Mothership: Expenses", 30, "h1.page-title");

        $I->amOnPage(sprintf(self::EXPENSE_EDIT_URL, 9999));
        $I->waitForElementVisible('#system-message-container joomla-alert[type=danger]', 30);
        $I->see('Expense not found.', '#system-message-container');
        $I->see('Mothership: Expenses', 'h1.page-title');
        $I->seeInCurrentUrl(self::EXPENSES_VIEW_ALL_URL);
    }
}
