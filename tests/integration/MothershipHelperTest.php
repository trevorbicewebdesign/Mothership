<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

class MothershipHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/MothershipHelper.php';

        $this->tester->setMothershipConfig([
            'company_name' => 'Your Company Name',
            'company_email' => 'company.email.@mailinator.com',
            'company_address' => '123 Nowhere St, CA, 12345',
            'company_address_1' => '123 Nowhere St',
            'company_address_2' => '',
            'company_city' => 'Nowhere',
            'company_state' => 'California',
            'company_zip' => '12345',
            'company_phone' => '555 555-5555',
        ]);
    }

    public function testGetMothershipOptions()
    {
        $options = MothershipHelper::getMothershipOptions();
        codecept_debug($options);

        $this->assertIsArray($options, 'Options should be an array');

        $this->assertArrayHasKey('company_name', $options, 'Options should contain company_name key');
        $this->assertArrayHasKey('company_email', $options, 'Options should contain company_email key');
        $this->assertArrayHasKey('company_address_1', $options, 'Options should contain company_address_1 key');
        $this->assertArrayHasKey('company_address_2', $options, 'Options should contain company_address_2 key');
        $this->assertArrayHasKey('company_city', $options, 'Options should contain company_city key');
        $this->assertArrayHasKey('company_state', $options, 'Options should contain company_state key');
        $this->assertArrayHasKey('company_zip', $options, 'Options should contain company_zip key');
        $this->assertArrayHasKey('company_phone', $options, 'Options should contain company_phone key');
        $this->assertArrayHasKey('company_default_rate', $options, 'Options should contain company_default_rate key');

        $this->assertEquals('Your Company Name', $options['company_name'], 'Company name should be "Your Company Name"');
        $this->assertEquals('123 Nowhere St', $options['company_address_1'], 'Company address 1 should be "123 Nowhere St"');
        $this->assertEquals('', $options['company_address_2'], 'Company address 2 should be empty');
        $this->assertEquals('Nowhere', $options['company_city'], 'Company city should be "Nowhere"');
        $this->assertEquals('California', $options['company_state'], 'Company state should be "California"');
        $this->assertEquals('12345', $options['company_zip'], 'Company zip should be "12345"');
        $this->assertEquals('555 555-5555', $options['company_phone'], 'Company phone should be "555 555-5555"');

        
        
    }

}