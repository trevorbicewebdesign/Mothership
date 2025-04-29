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

    public function testGenerateBodySuccess()
    {
        $body = "Test Template";
        $template = 'invoice.opened';
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
            'company_email' => 'test.company.email@malinator.com'
        ];

        $results = EmailService::generateBody($template, $data);
        codecept_debug($results);

        $this->assertArrayHasKey('html', $results, 'HTML key is missing in the result array.');
        $this->assertArrayHasKey('text', $results, 'Text key is missing in the result array.');

        $this->assertNotEmpty($results['html'], 'HTML content is empty.');
        $this->assertNotEmpty($results['text'], 'Text content is empty.');

        $this->assertEquals($body, $results['html'], 'Name in HTML content does not match the input data.');
        $this->assertEquals($body, $results['text'], 'Name in Text content does not match the input data.');
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
}