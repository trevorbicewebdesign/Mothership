<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * The email log in the administrator: the Emails list, one entry, resend, delete, and the Email
 * tab on the record an email was about.
 */
class MothershipAdminEmailsCest
{
    private $clientData;
    private $accountData;
    private $invoiceData;
    private $sentEmail;
    private $failedEmail;
    private $skippedEmail;

    /** Unique to this run, so Mailpit can be searched without picking up another test's mail. */
    private $token;

    const EMAILS_VIEW_ALL_URL = "/administrator/index.php?option=com_mothership&view=emails";
    const EMAIL_VIEW_URL = "/administrator/index.php?option=com_mothership&view=email&id=%s";
    const INVOICE_EDIT_URL = "/administrator/index.php?option=com_mothership&view=invoice&layout=edit&id=%s";

    const TBAR = "#toolbar";
    const LIST_ROWS = "#emailList tbody tr";

    const FAILED_REASON = "SMTP connect() failed.";
    const SKIPPED_REASON = "Not sent: the sender chose not to email this.";

    public function _before(AcceptanceTester $I)
    {
        $this->token = 'msmail' . bin2hex(random_bytes(4));

        $I->resetMothershipTables();

        $this->clientData = $I->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $this->accountData = $I->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->invoiceData = $I->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'number' => '4100',
            'total' => 250.00,
            'status' => 1,
        ]);

        $about = [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'object_type' => 'invoice',
            'object_id' => $this->invoiceData['id'],
        ];

        $this->sentEmail = $I->createMothershipEmail($about + [
            'template' => 'invoice.user-opened',
            'recipient' => "sent.{$this->token}@mailinator.com",
            'subject' => "Invoice #4100 Opened {$this->token}",
            'body_html' => "<p>Hello Pat, invoice 4100 is ready. {$this->token}</p>",
            'status' => 'sent',
            'created' => '2026-11-01 10:00:00',
            'sent_at' => '2026-11-01 10:00:01',
        ]);

        $this->failedEmail = $I->createMothershipEmail($about + [
            'template' => 'invoice.user-closed',
            'recipient' => "failed.{$this->token}@mailinator.com",
            'subject' => "Invoice #4100 Closed {$this->token}",
            'status' => 'failed',
            'error' => self::FAILED_REASON,
            'created' => '2026-11-02 10:00:00',
        ]);

        // About nothing in particular: no record, no client.
        $this->skippedEmail = $I->createMothershipEmail([
            'recipient' => "skipped.{$this->token}@mailinator.com",
            'subject' => "A note that was held back {$this->token}",
            'status' => 'skipped',
            'error' => self::SKIPPED_REASON,
            'created' => '2026-11-03 10:00:00',
        ]);

        $I->loginAsAdmin();
    }

    /**
     * Say yes to the toolbar's "are you sure". Joomla asks in a native confirm on older 5.x and
     * in its own dialog on newer releases.
     */
    private function confirm(AcceptanceTester $I): void
    {
        $I->wait(1);

        try {
            $I->acceptPopup();
        } catch (\Throwable $e) {
            $I->click("Yes", "joomla-dialog");
        }

        $I->wait(1);
    }

    /**
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipViewAllEmails(AcceptanceTester $I)
    {
        $I->amOnPage(self::EMAILS_VIEW_ALL_URL);
        $I->wait(1);
        $I->waitForText("Mothership: Emails", 20, "h1.page-title");

        $I->takeFullPageScreenshot("mothership-emails-view-all");

        $I->seeElement("joomla-toolbar-button#toolbar-resend", ['task' => "emails.resend"]);

        $I->seeNumberOfElements(self::LIST_ROWS, 3);

        // Newest first.
        $I->see("Not sent", self::LIST_ROWS . ":nth-child(1) .badge.bg-secondary");
        $I->see($this->skippedEmail['subject'], self::LIST_ROWS . ":nth-child(1)");
        $I->see(self::SKIPPED_REASON, self::LIST_ROWS . ":nth-child(1)");

        $I->see("Failed", self::LIST_ROWS . ":nth-child(2) .badge.bg-danger");
        $I->see($this->failedEmail['subject'], self::LIST_ROWS . ":nth-child(2)");
        $I->see($this->failedEmail['recipient'], self::LIST_ROWS . ":nth-child(2)");
        $I->see(self::FAILED_REASON, self::LIST_ROWS . ":nth-child(2) .ms-email-why.is-failed");

        $I->see("Sent", self::LIST_ROWS . ":nth-child(3) .badge.bg-success");
        $I->see($this->sentEmail['subject'], self::LIST_ROWS . ":nth-child(3)");
        $I->see($this->sentEmail['recipient'], self::LIST_ROWS . ":nth-child(3)");
        $I->see("invoice.user-opened", self::LIST_ROWS . ":nth-child(3)");
        $I->see("Invoice #4100", self::LIST_ROWS . ":nth-child(3) a");
        $I->see("Test Client", self::LIST_ROWS . ":nth-child(3)");
    }

    /**
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipFilterEmailsByStatus(AcceptanceTester $I)
    {
        $I->amOnPage(self::EMAILS_VIEW_ALL_URL . "&filter[status]=failed");
        $I->waitForText("Mothership: Emails", 20, "h1.page-title");

        $I->seeNumberOfElements(self::LIST_ROWS, 1);
        $I->see($this->failedEmail['subject'], self::LIST_ROWS);
        $I->dontSee($this->sentEmail['subject'], "#emailList");
        $I->dontSee($this->skippedEmail['subject'], "#emailList");
    }

    /**
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipViewSentEmail(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::EMAIL_VIEW_URL, $this->sentEmail['id']));
        $I->waitForText("Mothership: Email", 20, "h1.page-title");

        $I->takeFullPageScreenshot("mothership-email-view-sent");

        $I->see("Sent", "#ms-email .badge.bg-success");
        $I->see($this->sentEmail['subject'], "#ms-email h2");
        $I->see($this->sentEmail['recipient'], "#ms-email .ms-email-facts");
        $I->see("Invoice #4100", "#ms-email .ms-email-facts a");
        $I->see("Test Client", "#ms-email .ms-email-facts");
        $I->see("Test Account", "#ms-email .ms-email-facts");
        $I->see("invoice.user-opened", "#ms-email .ms-email-facts");
        $I->see("What was sent", "#ms-email");
        $I->dontSeeElement("#ms-email .alert-danger");

        // The body as it left, in its own frame.
        $I->switchToIFrame("#ms-email-body");
        $I->see("Hello Pat, invoice 4100 is ready. {$this->token}");
        $I->switchToIFrame();
    }

    /**
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipViewFailedEmailSaysWhy(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::EMAIL_VIEW_URL, $this->failedEmail['id']));
        $I->waitForText("Mothership: Email", 20, "h1.page-title");

        $I->see("Failed", "#ms-email .badge.bg-danger");
        $I->see("Why it did not go:", "#ms-email .alert-danger");
        $I->see(self::FAILED_REASON, "#ms-email .alert-danger");
        $I->see("What would have been sent", "#ms-email");
    }

    /**
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipViewMissingEmailGoesBackToTheList(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::EMAIL_VIEW_URL, 999999));
        $I->waitForText("Mothership: Emails", 20, "h1.page-title");

        $I->seeInCurrentUrl("view=emails");
        $I->see("That email is not in the log.", ".alert-message");
    }

    /**
     * A resend goes out again as it was, as a new entry that points back at the one it repeats.
     *
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipResendEmailFromTheList(AcceptanceTester $I)
    {
        $failed = $this->failedEmail;

        $I->amOnPage(self::EMAILS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Emails", 20, "h1.page-title");

        $I->checkOption("#emailList tbody input[name='cid[]'][value='{$failed['id']}']");
        $I->click("Resend", self::TBAR);
        $I->wait(1);

        $I->waitForText("Email resent.", 20, ".alert-message");
        $I->seeInCurrentUrl("view=emails");
        $I->seeNumberOfElements(self::LIST_ROWS, 4);

        $I->takeFullPageScreenshot("mothership-emails-resend");

        // The original keeps its record; the new entry says how the second attempt went.
        $I->seeInDatabase("jos_mothership_emails", ['id' => $failed['id'], 'status' => 'failed']);
        $I->seeInDatabase("jos_mothership_emails", [
            'resend_of' => $failed['id'],
            'status' => 'sent',
            'recipient' => $failed['recipient'],
            'subject' => $failed['subject'],
            'template' => 'invoice.user-closed',
            'object_type' => 'invoice',
            'object_id' => $this->invoiceData['id'],
            'client_id' => $this->clientData['id'],
        ]);

        // And the message really left.
        $mailId = $I->getEmailBySubject($failed['subject']);
        $mail = $I->getEmailById($mailId);
        $I->assertSame($failed['recipient'], $mail['To'][0]['Address'] ?? '', 'resent to the original recipient');
    }

    /**
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipResendEmailFromItsOwnPage(AcceptanceTester $I)
    {
        $sent = $this->sentEmail;

        $I->amOnPage(sprintf(self::EMAIL_VIEW_URL, $sent['id']));
        $I->waitForText("Mothership: Email", 20, "h1.page-title");

        $I->click("Resend", self::TBAR);
        $I->wait(1);

        $I->waitForText("Email resent.", 20, ".alert-message");
        $I->seeInCurrentUrl("view=email&id={$sent['id']}");

        $I->seeInDatabase("jos_mothership_emails", ['resend_of' => $sent['id'], 'status' => 'sent']);
        $I->getEmailBySubject($sent['subject']);
    }

    /**
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipDeleteSingleEmail(AcceptanceTester $I)
    {
        $email = $this->skippedEmail;

        $I->amOnPage(self::EMAILS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Emails", 20, "h1.page-title");

        $I->seeNumberOfElements(self::LIST_ROWS, 3);

        $I->checkOption("#emailList tbody input[name='cid[]'][value='{$email['id']}']");
        $I->click("Actions");
        $I->click("Delete", self::TBAR);
        $this->confirm($I);

        $I->waitForText("Email log entry deleted.", 20, ".alert-message");
        $I->seeInCurrentUrl("view=emails");
        $I->seeNumberOfElements(self::LIST_ROWS, 2);
        $I->dontSee($email['subject'], "#emailList");

        $I->takeFullPageScreenshot("mothership-emails-delete-single");

        $I->dontSeeInDatabase("jos_mothership_emails", ['id' => $email['id']]);
        $I->seeInDatabase("jos_mothership_emails", ['id' => $this->sentEmail['id']]);
        $I->seeInDatabase("jos_mothership_emails", ['id' => $this->failedEmail['id']]);
    }

    /**
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipDeleteMultipleEmails(AcceptanceTester $I)
    {
        $I->amOnPage(self::EMAILS_VIEW_ALL_URL);
        $I->waitForText("Mothership: Emails", 20, "h1.page-title");

        foreach ([$this->sentEmail, $this->failedEmail] as $email) {
            $I->checkOption("#emailList tbody input[name='cid[]'][value='{$email['id']}']");
        }
        $I->click("Actions");
        $I->click("Delete", self::TBAR);
        $this->confirm($I);

        $I->waitForText("2 email log entries deleted.", 20, ".alert-message");
        $I->seeNumberOfElements(self::LIST_ROWS, 1);
        $I->see($this->skippedEmail['subject'], "#emailList");

        $I->dontSeeInDatabase("jos_mothership_emails", ['id' => $this->sentEmail['id']]);
        $I->dontSeeInDatabase("jos_mothership_emails", ['id' => $this->failedEmail['id']]);
        $I->seeInDatabase("jos_mothership_emails", ['id' => $this->skippedEmail['id']]);
    }

    /**
     * The invoice an email was about lists it on its own Email tab.
     *
     * @group backend
     * @group email
     * @group backend-email
     */
    public function MothershipInvoiceEmailTabListsItsEmails(AcceptanceTester $I)
    {
        $I->amOnPage(sprintf(self::INVOICE_EDIT_URL, $this->invoiceData['id']));
        $I->waitForText("Mothership: Edit Invoice", 20, "h1.page-title");

        // Two of the three entries are about this invoice.
        $I->click("Email (2)");
        $I->wait(1);

        $I->takeFullPageScreenshot("mothership-invoice-email-tab");

        $I->seeNumberOfElements("#ms-email-tab tbody tr", 2);
        $I->see($this->failedEmail['subject'], "#ms-email-tab tbody tr:nth-child(1) a");
        $I->see(self::FAILED_REASON, "#ms-email-tab tbody tr:nth-child(1) .text-danger");
        $I->see($this->sentEmail['subject'], "#ms-email-tab tbody tr:nth-child(2) a");
        $I->dontSee($this->skippedEmail['subject'], "#ms-email-tab");

        // Each entry opens in the log.
        $I->click($this->sentEmail['subject'], "#ms-email-tab");
        $I->waitForText("Mothership: Email", 20, "h1.page-title");
        $I->seeInCurrentUrl("view=email&id={$this->sentEmail['id']}");
    }
}
