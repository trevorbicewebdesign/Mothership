<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;

class MothershipEmailServiceTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/EmailService.php';
    }


    /**
     * @dataProvider templateDataProvider
     */
    public function testGenerateBodySuccess($template, $expectedBody)
    {
        $data = [
            'fname' => 'John',
            'admin_fname' => 'Admin',
        ];

        $results = EmailService::generateBody($template, $data);
        codecept_debug($results);

        $this->assertArrayHasKey('html', $results, 'HTML key is missing in the result array.');
        $this->assertArrayHasKey('text', $results, 'Text key is missing in the result array.');

        $this->assertNotEmpty($results['html'], 'HTML content is empty.');
        $this->assertNotEmpty($results['text'], 'Text content is empty.');

        $this->assertStringContainsString($expectedBody, $results['html'], 'HTML content does not match the expected body.');
        $this->assertStringContainsString($expectedBody, $results['text'], 'Text content does not match the expected body.');
    }

    public function templateDataProvider()
    {
        return [
            ['invoice.user-opened', 'Hello John,'],
            ['invoice.user-opened', 'You have a new invoice.'],
            ['invoice.user-closed', 'Hello John,'],
            ['invoice.user-closed', 'Your invoice has been marked as closed.'],
            ['payment.admin-confirmed', 'Hello Admin,'],
            ['payment.admin-confirmed', 'A payment has been confirmed.'],
            ['payment.admin-pending', 'Hello Admin,'],
            ['payment.admin-pending', 'A new payment is pending your confirmation.'],
            ['payment.user-confirmed', 'Hello John,'],
            ['payment.user-confirmed', 'Your payment has been confirmed.'],
        ];
    }

    public function testGenerateBodyNotFound()
    {
        $template = 'non_existent_template';
        $data = [
            'name' => 'Test User',
            'account_id' => 12345,
            'client_id' => 67890,
            
        ];

        $results = EmailService::generateBody($template, $data);
        codecept_debug($results);

        $this->assertArrayHasKey('html', $results, 'HTML key is missing in the result array.');
        $this->assertArrayHasKey('text', $results, 'Text key is missing in the result array.');

        $this->assertEmpty($results['html'], 'HTML content should be empty for non-existent template.');
        $this->assertEmpty($results['text'], 'Text content should be empty for non-existent template.');
    }

    public function testSendEmailSuccess()
    {
        EmailService::sendTemplate('payment.admin-pending', 
        'test.smith@mailinator.com', 
        'New Pending Payment for Pay By Check', 
        [
            'fname' => 'John',
            'admin_fname' => 'Admin',
        ]);

        $email_id = $this->tester->getEmailBySubject("New Pending Payment for Pay By Check");        
        $email = $this->tester->getEmailById($email_id);
        codecept_debug($email);
        $this->assertEquals($email['Subject'], "New Pending Payment for Pay By Check", 'Email subject does not match.');
    }

}