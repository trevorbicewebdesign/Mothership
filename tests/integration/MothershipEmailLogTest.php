<?php

namespace Tests\Integration;

use Joomla\CMS\Factory;
use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Service\EmailLog;
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;

/**
 * The email log: every send EmailService attempts leaves a row saying what happened.
 */
class MothershipEmailLogTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;
    protected $invoiceData;

    /** Unique to this run, so Mailpit can be searched without picking up another test's mail. */
    protected string $token;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/EmailLog.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/EmailService.php';

        $this->token = 'mslog' . bin2hex(random_bytes(4));

        // Text::_() only knows the component's strings once its language file is loaded.
        Factory::getLanguage()->load('com_mothership', JPATH_ADMINISTRATOR . '/components/com_mothership');

        // Joomla caches the component's params on first use for the whole run, and rendering an
        // email reads them. Set what the other integration tests expect to find.
        $this->tester->setMothershipConfig([
            'company_name' => 'Your Company Name',
            'company_address' => '123 Nowhere St, CA, 12345',
            'company_address_1' => '123 Nowhere St',
            'company_address_2' => '',
            'company_city' => 'Nowhere',
            'company_state' => 'California',
            'company_zip' => '12345',
            'company_phone' => '555 555-5555',
        ]);

        $this->clientData = $this->tester->createMothershipClient([
            'name'  => 'Email Log Client',
            'email' => 'email.log@mailinator.com',
        ]);

        $this->accountData = $this->tester->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name'      => 'Email Log Account',
        ]);

        $this->invoiceData = $this->tester->createMothershipInvoice([
            'client_id'  => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'number'     => '4100',
            'total'      => 250.00,
            'due_date'   => '2026-11-01',
        ]);
    }

    private function invoiceData(): array
    {
        return [
            'fname'   => 'Pat',
            'lname'   => 'Example',
            'invoice' => (object) $this->invoiceData,
            'client'  => (object) $this->clientData,
        ];
    }

    private function rowBySubject(string $subject): object
    {
        $id = $this->tester->grabFromDatabase('jos_mothership_emails', 'id', ['subject' => $subject]);
        $this->assertNotEmpty($id, 'No log entry for ' . $subject);

        return EmailLog::find((int) $id);
    }

    public function testASentEmailIsRecordedWithWhatItWasAbout()
    {
        $subject = 'Invoice #4100 Opened ' . $this->token;

        $ok = EmailService::sendTemplate(
            'invoice.user-opened',
            $this->token . '.to@mailinator.com',
            $subject,
            $this->invoiceData(),
            EmailService::about('invoice', (object) $this->invoiceData)
        );

        $this->assertTrue($ok);
        $this->assertNull(EmailService::lastError());

        $row = $this->rowBySubject($subject);
        $this->assertSame(EmailLog::STATUS_SENT, $row->status);
        $this->assertSame($this->token . '.to@mailinator.com', $row->recipient);
        $this->assertSame('invoice.user-opened', $row->template);
        $this->assertSame('invoice', $row->object_type);
        $this->assertEquals($this->invoiceData['id'], $row->object_id);
        $this->assertEquals($this->clientData['id'], $row->client_id);
        $this->assertEquals($this->accountData['id'], $row->account_id);
        $this->assertNotEmpty($row->sent_at, 'sent_at is stamped when the mail server accepts the message');
        $this->assertNotEmpty($row->message_id, 'the Message-ID the mailer generated is kept');
        $this->assertNull($row->error);
        $this->assertStringContainsString('Hello Pat', $row->body_html, 'the rendered body is kept');

        // The names the screens show come with the row.
        $this->assertSame('Email Log Client', $row->client_name);
        $this->assertSame('4100', (string) $row->invoice_number);
        $this->assertSame('Invoice #4100', EmailLog::aboutLabel($row));
        $this->assertStringContainsString('task=invoice.edit&id=' . (int) $this->invoiceData['id'], EmailLog::aboutLink($row));

        // And the message really left.
        $this->assertNotEmpty($this->tester->getEmailBySubject($subject), 'Mailpit has the message');
    }

    public function testASkippedEmailIsRecordedAsNotSentAndDoesNotLeave()
    {
        $subject = 'Invoice #4100 Opened skipped ' . $this->token;

        $ok = EmailService::sendTemplate(
            'invoice.user-opened',
            $this->token . '.skip@mailinator.com',
            $subject,
            $this->invoiceData(),
            EmailService::about('invoice', (object) $this->invoiceData, ['skip' => true])
        );

        $this->assertFalse($ok);
        $this->assertNull(EmailService::lastError(), 'a skip is not an error');

        $row = $this->rowBySubject($subject);
        $this->assertSame(EmailLog::STATUS_SKIPPED, $row->status);
        $this->assertSame(EmailService::SKIPPED_BY_CHOICE, $row->error);
        $this->assertNull($row->sent_at);
        $this->assertStringContainsString('Hello Pat', $row->body_html, 'what would have gone is kept');

        // The Mailpit helper throws when the search finds nothing, which is what a skip should do.
        try {
            $this->tester->getEmailBySubject($subject);
            $this->fail('nothing should have reached Mailpit');
        } catch (\Exception $e) {
            $this->assertStringContainsString('No messages found', $e->getMessage());
        }
    }

    public function testAFailedEmailIsRecordedWithTheReasonAndDoesNotThrow()
    {
        $subject = 'Invoice #4100 Opened failed ' . $this->token;

        // An address the mailer refuses, so the failure happens before anything leaves.
        $ok = EmailService::sendTemplate(
            'invoice.user-opened',
            'not-an-address',
            $subject,
            $this->invoiceData(),
            EmailService::about('invoice', (object) $this->invoiceData)
        );

        $this->assertFalse($ok);
        $this->assertNotEmpty(EmailService::lastError());

        $row = $this->rowBySubject($subject);
        $this->assertSame(EmailLog::STATUS_FAILED, $row->status);
        $this->assertSame(EmailService::lastError(), $row->error);
        $this->assertNull($row->sent_at);
        $this->assertSame('not-an-address', $row->recipient);
    }

    public function testTheEntriesAboutOneRecordComeBackNewestFirst()
    {
        $first  = 'First about the invoice ' . $this->token;
        $second = 'Second about the invoice ' . $this->token;
        $other  = 'About another invoice ' . $this->token;

        EmailService::sendEmail($this->token . '.a@mailinator.com', $first, ['html' => '<p>one</p>'], EmailService::about('invoice', (object) $this->invoiceData));
        EmailService::sendEmail($this->token . '.b@mailinator.com', $second, ['html' => '<p>two</p>'], EmailService::about('invoice', (object) $this->invoiceData));
        EmailService::sendEmail($this->token . '.c@mailinator.com', $other, ['html' => '<p>three</p>'], ['object_type' => 'invoice', 'object_id' => 999999]);

        $rows = EmailLog::forObject('invoice', (int) $this->invoiceData['id']);

        $this->assertCount(2, $rows);
        $this->assertSame($second, $rows[0]->subject, 'newest first');
        $this->assertSame($first, $rows[1]->subject);

        $this->assertSame([], EmailLog::forObject('invoice', 999998), 'a record nothing was sent about has no entries');
    }

    public function testAResendPointsBackAtTheEntryItRepeats()
    {
        $subject = 'Resend me ' . $this->token;

        EmailService::sendEmail($this->token . '.r@mailinator.com', $subject, ['html' => '<p>again</p>'], EmailService::about('invoice', (object) $this->invoiceData));
        $original = $this->rowBySubject($subject);

        $ok = EmailService::sendEmail(
            [$original->recipient],
            $original->subject,
            ['html' => $original->body_html],
            EmailService::about('invoice', (object) $this->invoiceData, ['resend_of' => $original->id, 'template' => $original->template])
        );

        $this->assertTrue($ok);

        $rows = EmailLog::forObject('invoice', (int) $this->invoiceData['id']);
        $this->assertCount(2, $rows);
        $this->assertEquals($original->id, $rows[0]->resend_of);
        $this->assertSame(EmailLog::STATUS_SENT, $rows[0]->status);
        $this->assertSame('<p>again</p>', $rows[0]->body_html);
    }

    public function testStatusPillsReadAsWords()
    {
        $this->assertStringContainsString('bg-success', EmailLog::pill('sent'));
        $this->assertStringContainsString('bg-danger', EmailLog::pill('failed'));
        $this->assertStringContainsString('bg-secondary', EmailLog::pill('skipped'));
        $this->assertStringContainsString('bg-warning', EmailLog::pill('pending'));
        $this->assertStringContainsString('bg-warning', EmailLog::pill(''), 'no status reads as unknown');
    }
}
