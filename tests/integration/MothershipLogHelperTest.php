<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;

class MothershipLogHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;
    protected $invoiceData;
    protected $paymentData;
    protected $projectData;
    protected $domainData;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/LogHelper.php';

        $this->tester->resetMothershipTables();

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Client',
        ]);

        $this->accountData = $this->tester->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->projectData = $this->tester->createMothershipProject([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'name' => 'Test Project',
            'type' => 'website',
        ]);

        $this->domainData = $this->tester->createMothershipDomain([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'project_id' => $this->projectData['id'],
            'name' => 'google.com',
        ]);

        $this->invoiceData = $this->tester->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => 175.00,
            'status' => '2',
        ]);

        $this->paymentData = $this->tester->createMothershipPayment([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'amount' => 175.00,
            'payment_date' => date('Y-m-d H:i:s'),
            'status' => '4',
            'payment_method' => 'manual'
        ]);
    }

    public function testLog()
    {
        $params = [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'object_type' => 'invoice',
            'object_id' => $this->invoiceData['id'],
            'action' => 'viewed',
            'meta' => [],
            'user_id' => 1,
        ];

        $result = LogHelper::log($params);
        codecept_debug($result);

        $this->assertTrue($result, 'Log entry was not created successfully.');

        $this->tester->seeInDatabase('jos_mothership_logs', [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'object_type' => 'invoice',
            'object_id' => $this->invoiceData['id'],
            'action' => 'viewed',
            'user_id' => 1,
        ]);
    }

    public function objectTypeProvider()
    {
        return [
            'client' => ['client'],
            'account' => ['account'],
            'project' => ['project'],
            'domain' => ['domain'],
            'invoice' => ['invoice'],
            'payment' => ['payment'],
        ];
    }

    /**
     * @dataProvider objectTypeProvider
     */
    public function testLogObjectViewed($objectType)
    {
        switch($objectType){
            case 'invoice':
                $objectId = $this->invoiceData['id'];
                break;
            case 'payment':
                $objectId = $this->paymentData['id'];
                break;
            case 'project':
                $objectId = $this->projectData['id'];
                break;
            case 'client':
                $objectId = $this->clientData['id'];
                break;
            case 'account':
                $objectId = $this->accountData['id'];
                break;
            case 'domain':
                $objectId = $this->domainData['id'];
                break;
            default:
                $this->fail("Invalid object type: $objectType");
        }
        codecept_debug("Logging object viewed: $objectType with ID: $objectId");
        $result = LogHelper::logObjectViewed($objectType, $objectId, $this->clientData['id'], $this->accountData['id']);
        codecept_debug($result);

        $this->assertTrue($result, 'Log entry was not created successfully.');

        $this->tester->seeInDatabase('jos_mothership_logs', [
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'object_type' => $objectType,
            'object_id' => $objectId,
            'action' => 'viewed',
            // 'user_id' => 1,
        ]);
    }
}
