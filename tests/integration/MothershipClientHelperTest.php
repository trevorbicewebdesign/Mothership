<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;

class MothershipClientHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/ClientHelper.php';

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Client',
        ]);

    }

    public function testGetClientSuccess()
    {
        $client = ClientHelper::getClient($this->clientData['id']);

        codecept_debug($client);
        $this->assertIsObject($client);

        $criteria = [
            'id' => $client->id,
            'name' => 'Test Client',
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_clients', $criteria);
    }

    public function testGetClientInvalidId()
    {
        try{
            ClientHelper::getClient(9999);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Client ID 9999 not found.', $e->getMessage());
        }
    }

    public function testGetClientEmptyId()
    {
        try{
            ClientHelper::getClient('');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Client ID cannot be null or empty.', $e->getMessage());
        }
    }
}