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
    }


    /**
     * @dataProvider templateDataProvider
     */
    public function testGenerateBodySuccess($template, $expectedBody)
    {
        $data = [
            'fname' => 'John',
            'admin_fname' => 'Admin',
            'payment' => (object) [
                'id' => 1,
                'amount' => 100.00,
                'payment_method' => 'paybycheck',
                'payment_date' => "2023-10-01",
            ],
            'invoice' => (object) [
                'id' => 1,
                'number' => '2023',
                'amount' => 100.00,
            ],
            'client' => (object) [
                'id' => 1,
                'name' => 'Test Client',
            ],
            'confirm_link' => 'index.php?option=com_mothership&task=payment.confirm&id=1',
            'view_link' => 'index.php?option=com_mothership&view=invoice&id=1',
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
            ['payment.admin-pending', 'A new paybycheck payment for $100.00 has been initiated by Test Client'],
            ['payment.user-confirmed', 'Hello John,'],
            ['payment.user-confirmed', 'Your payment has been confirmed.'],
        ];
    }

    /*
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
    */

    public function testSendEmailSuccess()
    {
        EmailService::sendTemplate('payment.admin-pending', 
        'test.smith@mailinator.com', 
        'New Pending Payment for Pay By Check', 
        [
            'fname' => 'John',
            'admin_fname' => 'Admin',
            'payment' => (object) [
                'id' => 1,
                'amount' => 100.00,
                'payment_method' => 'paybycheck',
                'payment_date' => "2023-10-01",
            ],
            'invoice' => (object) [
                'id' => 1,
                'number' => '2023',
                'amount' => 100.00,
            ],
            'client' => (object) [
                'id' => 1,
                'name' => 'Test Client',
            ],
            'confirm_link' => "http://localhost:8080/administrator/index.php?option=com_mothership&task=payment.confirm&id=1",
            'view_link' => "http://localhost:8080/administrator/index.php?option=com_mothership&view=invoice&id=1",
        ]);

        $email_id = $this->tester->getEmailBySubject("New Pending Payment for Pay By Check");        
        $email = $this->tester->getEmailById($email_id);
        $this->assertEquals($email['Subject'], "New Pending Payment for Pay By Check", 'Email subject does not match.');
    }

}