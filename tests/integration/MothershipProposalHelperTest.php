<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\ProposalHelper;

class MothershipProposalHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;
    protected $proposalData;
    protected $proposalItemData = [];

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/EmailService.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/ProposalHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/ClientHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/AccountHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/LogHelper.php';
        

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Client',
            'owner_user_id' => 1,
        ]);

        $this->accountData = $this->tester->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->proposalData = $this->tester->createMothershipProposal([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '175.00',
            'number' => 1001,
            'expires' => NULL,
            'created' => date('Y-m-d H:i:s'),
            'status' => 1,
        ]);

        $this->proposalItemData[] = $this->tester->createMothershipProposalItem([
            'proposal_id' => $this->proposalData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'hours' => '1',
            'minutes' => '30',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);
    }

    public function testGetProposalSuccess()
    {
        $proposal = ProposalHelper::getProposal($this->proposalData['id']);

        codecept_debug($proposal);
        $this->assertIsObject($proposal);

        $criteria = [
            'id' => $proposal->id,
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_proposals', $criteria);
    }

    public function testGetProposalInvalidId()
    {
        try{
            ProposalHelper::getProposal(9999);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Proposal ID 9999 not found.', $e->getMessage());
        }
    }

    public function getStatusProvider()
    {
        return [
            [1, 'Draft'],
            [2, 'Pending'],
            [3, 'Approved'],
            [4, 'Declined'],
            [5, 'Cancelled'],
            [6, 'Expired'],
            [7, 'Unknown'],
        ];
    }

    /**
     * @dataProvider getStatusProvider
     */
    public function testGetStatus($status_id, $expected)
    {
        codecept_debug("Status ID is $status_id");
        $status = ProposalHelper::getStatus($status_id);

        codecept_debug($status);
        $this->assertIsString($status);
        $this->assertEquals($expected, $status);
    }

    public function updateProposalStatusProvider()
    {
        return [
            [1, 'Draft'],
            [2, 'Pending'],
            [3, 'Approved'],
            [4, 'Declined'],
            [5, 'Cancelled'],
            [6, 'Expired'],
        ];
    }

    /**
     * @dataProvider updateProposalStatusProvider
     */
    public function testUpdateProposalStatus($status_id)
    {
        $proposalId = $this->proposalData['id'];

        $this->tester->seeInDatabase('jos_mothership_proposals', [
            'id' => $proposalId,
            'status' => 1,
        ]);

        $results = ProposalHelper::updateProposalStatus((object) $this->proposalData, $status_id);

        codecept_debug($results);
        $this->assertTrue($results);

        $criteria = [
            'id' => $proposalId,
            'status' => $status_id,
        ];
        codecept_debug($criteria);

        $this->tester->seeInDatabase('jos_mothership_proposals', $criteria);

    }
}