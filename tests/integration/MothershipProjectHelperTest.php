<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\ProjectHelper;

class MothershipProjectHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;
    protected $projectData;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/ProjectHelper.php';

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Client',
            'email' => 'test.smith@mailinator.com',
            'owner_user_id' => 1,
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
            'metadata' => json_encode([
                'primary_url' => 'https://example.com',
            ]),
            'status' => 1,
        ]);

    }

    public function testScanWebsiteProject()
    {
        $projectId = $this->projectData['id'];
        $clientId = $this->clientData['id'];
        $accountId = $this->accountData['id'];

        $url = 'https://mothership.trevorbice.com';

        // Call the method to test
        $result = ProjectHelper::scanWebsiteProject($url);
        codecept_debug($result);

        // Check if the result is an array and contains expected keys
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testDetectJoomla()
    {
        $html = '<html><head><script type="application/json" class="joomla-script-options new">{"joomla.jtext":{"ERROR":"Error","MESSAGE":"Message","NOTICE":"Notice","WARNING":"Warning","JCLOSE":"Close","JOK":"OK","JOPEN":"Open"},"system.paths":{"root":"","rootFull":"https:\/\/mothership.trevorbice.com\/","base":"","baseFull":"https:\/\/mothership.trevorbice.com\/"},"csrf.token":"72a4dbf791324e96e8c9c234b204c6e1"}</script></head><body></body></html>';
        $isJoomla = ProjectHelper::detectJoomla([], $html);
        codecept_debug($isJoomla);

        $this->tester->assertTrue($isJoomla, 'Joomla detection failed.');
    }

    public function testDetectJoomlaFailed()
    {
        $html = '<html><head></head><body></body></html>';
        $isJoomla = ProjectHelper::detectJoomla([], $html);
        codecept_debug($isJoomla);

        $this->tester->assertFalse($isJoomla, 'Joomla detection failed.');
    }


    public function testDetectWordpress()
    {
        $html = '<html><head><meta name="generator" content="WordPress 6.7.2" /></head><body></body></html>';
        $isWordpress = ProjectHelper::detectWordpress([], $html);
        codecept_debug($isWordpress);

        $this->tester->assertTrue($isWordpress, 'Wordpress detection failed.');
    }

    public function testDetectWordpressFailed()
    {
        $html = '<html><head></head><body></body></html>';
        $isWordpress = ProjectHelper::detectWordpress([], $html);
        codecept_debug($isWordpress);

        $this->tester->assertFalse($isWordpress, 'Wordpress detection failed.');
    }

    public function testGetGeneratorMeta()
    {
        $html = '<html><head><meta name="generator" content="WordPress 6.7.2" /></head><body></body></html>';
        $results = ProjectHelper::getGeneratorMeta( $html);
        codecept_debug($results);

        $this->tester->assertEquals('WordPress 6.7.2', $results, 'Wordpress detection failed.');
    }

    public function testGetGeneratorMetaFailed()
    {
        $html = '<html><head></head><body></body></html>';
        $results = ProjectHelper::getGeneratorMeta( $html);
        codecept_debug($results);

        $this->tester->assertEquals(NULL, $results, 'Wordpress detection failed.');
    }
}