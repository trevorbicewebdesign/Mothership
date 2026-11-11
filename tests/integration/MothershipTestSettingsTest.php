<?php

namespace Tests\Integration;

use Joomla\CMS\Component\ComponentHelper;
use \Tests\Support\IntegrationTester;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiService;

/**
 * The test helpers that write settings: a setting written after Joomla has already read the
 * component's params (or its list of plugins) in this process must be the one read next.
 *
 * Joomla keeps both for the life of the process, and the suite is one process, so without the
 * helpers dropping that copy a test saw whatever an earlier test had caused to be loaded.
 */
class MothershipTestSettingsTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/Ai/AiResult.php';
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Service/Ai/AiService.php';
    }

    public function testAComponentSettingWrittenAfterTheParamsWereReadIsRead()
    {
        // Something has read the params already.
        ComponentHelper::getParams('com_mothership')->get('company_name');

        $this->tester->setMothershipConfig(['company_name' => 'Written Later Ltd']);
        $this->assertSame('Written Later Ltd', ComponentHelper::getParams('com_mothership')->get('company_name'));

        $this->tester->setMothershipConfig(['company_name' => 'Written Last Ltd']);
        $this->assertSame('Written Last Ltd', ComponentHelper::getParams('com_mothership')->get('company_name'));
    }

    public function testAiSwitchedOnAndOffAfterItWasAskedAboutIsSeen()
    {
        $this->assertFalse(AiService::isEnabled());

        // A placeholder key: enough for the connector to call itself configured. Nothing is sent.
        $this->tester->haveMothershipAi(true, ['api_key' => 'not-a-real-key']);
        $this->assertTrue(AiService::isEnabled());

        $this->tester->haveMothershipAi(true);
        $this->assertFalse(AiService::isEnabled(), 'enabled, but the connector has no key');

        $this->tester->haveMothershipAi(false);
        $this->assertFalse(AiService::isEnabled());
    }
}
