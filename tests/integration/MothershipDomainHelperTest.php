<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Helper\DomainHelper;

class MothershipDomainHelperTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/DomainHelper.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/vendor/autoload.php';

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Client',
        ]);

    }

    public function testScanDonain()
    {
        $domain = "trevorbice.com";
        $results = DomainHelper::scanDomain($domain);

        codecept_debug($results);
        
    }
}