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
            'name' => 'Test User',
            'account_id' => 12345,
            'account_name' => 'Test Account',
            'client_id' => 67890,
            'client_name' => 'Test Client',
            'invoice_number' => 123456,
            'fname' => 'John',
            'invoice_due_date' => '2023-12-31',
            'company_name' => 'Test Company',
            'company_address' => '123 Test St, Test City, TC 12345',
            'company_address_1' => '123 Test St',
            'company_address_2' => '',
            'company_city' => 'Test City',
            'company_state' => 'TC',
            'company_zip' => '12345',
            'company_phone' => '123 456-7890',
            'company_email' => 'test.company.email@malinator.com',
            'company_default_rate' => 100.00,
        ];

        $results = EmailService::generateBody($template, $data);
        codecept_debug($results);

        $this->assertArrayHasKey('html', $results, 'HTML key is missing in the result array.');
        $this->assertArrayHasKey('text', $results, 'Text key is missing in the result array.');

        $this->assertNotEmpty($results['html'], 'HTML content is empty.');
        $this->assertNotEmpty($results['text'], 'Text content is empty.');

        $this->assertEquals($expectedBody, $results['html'], 'HTML content does not match the expected body.');
        $this->assertEquals($expectedBody, $results['text'], 'Text content does not match the expected body.');
    }

    public function templateDataProvider()
    {
        return [
            ['invoice.user-opened', 'Test User Invoice Opened'],
            ['invoice.user-closed', 'Test User Invoice Closed'],
            ['payment.admin-confirmed', 'Test Admin Payment Confirmed'],
            ['payment.admin-pending', 'Test Admin Payment Pending'],
            ['payment.user-confirmed', 'Test User Payment Confirmed'],
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
        []);

        $email_id = $this->tester->getEmailBySubject("New Pending Payment for Pay By Check");        
        $email = $this->tester->getEmailById($email_id);
        codecept_debug($email);
        $this->assertEquals($email['Subject'], "New Pending Payment for Pay By Check", 'Email subject does not match.');
    }

}