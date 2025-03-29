<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;

class MothershipAccountHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/AccountHelper.php';

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Account',
        ]);

        $this->accountData = $this->tester->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

    }

    public function testGetAccountSuccess()
    {
        $account = AccountHelper::getAccount($this->accountData['id']);

        codecept_debug($account);
        $this->assertIsObject($account);

        $criteria = [
            'id' => $account->id,
            'name' => 'Test Account',
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_accounts', $criteria);
    }

    public function testGetAccountInvalidId()
    {
        try{
            AccountHelper::getAccount(9999);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Account ID 9999 not found.', $e->getMessage());
        }
    }
}