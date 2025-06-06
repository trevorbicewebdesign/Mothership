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

    public function testScanDomain()
    {
        $domain = "google.com";
        $results = DomainHelper::scanDomain($domain);

        codecept_debug($results);
        
        $this->assertArrayHasKey('domain', $results);
        $this->assertArrayHasKey('creation_date', $results);
        $this->assertArrayHasKey('expiration_date', $results);
        $this->assertArrayHasKey('updated_date', $results);
        $this->assertArrayHasKey('registrar', $results);
        $this->assertArrayHasKey('reseller', $results);
        $this->assertArrayHasKey('epp_status', $results);
        $this->assertArrayHasKey('name_servers', $results);
        $this->assertArrayHasKey('dns_provider', $results);
        $this->assertArrayHasKey('data', $results);
        $this->assertArrayHasKey('extra', $results);
        $this->assertArrayHasKey('rawText', $results);

        $this->assertEquals($domain, $results['domain']);
        $this->assertEquals('874306800', $results['creation_date']);
        $this->assertEquals('1852441200', $results['expiration_date']);
        $this->assertEquals('1722565053', $results['updated_date']);
        $this->assertEquals('MarkMonitor, Inc.', $results['registrar']);
        $this->assertEquals(NULL, $results['reseller']);
        
        $this->assertContains('ns1.google.com', $results['name_servers']);
        $this->assertContains('ns2.google.com', $results['name_servers']);
        $this->assertContains('ns3.google.com', $results['name_servers']);
        $this->assertContains('ns4.google.com', $results['name_servers']);
        
        $this->assertEquals('google', $results['dns_provider']);

        $this->assertEquals([
            'clientUpdateProhibited', 
            'clientTransferProhibited', 
            'clientDeleteProhibited', 
            'serverUpdateProhibited',
            'serverTransferProhibited', 
            'serverDeleteProhibited', 
            
        ],$results['epp_status']);
    }

    public function nameServerProvider()
    {
        return [
            'Google DNS' => [
                ['ns1.google.com', 'ns2.google.com', 'ns3.google.com', 'ns4.google.com'],
                'google'
            ],
            'Cloudflare DNS' => [
                ['erin.ns.cloudflare.com', 'mark.ns.cloudflare.com'],
                'cloudflare'
            ],
            'Unknown DNS' => [
                ['ns1.unknown.com', 'ns2.unknown.com'],
                'unknown'
            ],
            // Add no DNS
            // Add two different DNS servers
        ];
    }

    /**
     * @dataProvider nameServerProvider
     */
    public function testGetDnsProvider($name_servers, $expected_provider)
    {
        $dns_provider = DomainHelper::getDnsProvider($name_servers);
        codecept_debug($dns_provider);
        codecept_debug($expected_provider);
        

        $this->assertEquals($expected_provider, $dns_provider);
    }

    public function testGetDomainStatus()
    {
        $epp_status = [
            'ok https://icann.org/epp#ok',
            'clientUpdateProhibited (https://icann.org/epp#clientUpdateProhibited)',
            'clientTransferProhibited (https://icann.org/epp#clientTransferProhibited)',
            'clientDeleteProhibited (https://icann.org/epp#clientDeleteProhibited)',
            'serverUpdateProhibited (https://icann.org/epp#serverUpdateProhibited)',
            'serverTransferProhibited (https://icann.org/epp#serverTransferProhibited)',
            'serverDeleteProhibited (https://icann.org/epp#serverDeleteProhibited)'
        ];

        $status = DomainHelper::getDomainStatus($epp_status);
        codecept_debug($status);

        $this->assertEquals([
            'ok',
            'clientUpdateProhibited', 
            'clientTransferProhibited', 
            'clientDeleteProhibited', 
            'serverUpdateProhibited',
            'serverTransferProhibited', 
            'serverDeleteProhibited', 
            
        ],$status);
    }
}