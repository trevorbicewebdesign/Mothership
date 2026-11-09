<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use \PlgMothershipPaymentPaybycheck;
use Joomla\CMS\Factory;

class MothershipPaymentPluginPayByCheckTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected PlgMothershipPaymentPaybycheck $plugin;

    protected function _before()
    {
        require_once JPATH_ROOT . '/plugins/mothership-payment/paybycheck/paybycheck.php';

        $this->plugin = new PlgMothershipPaymentPaybycheck(Factory::getApplication()->getDispatcher(), [
            'name' => 'paybycheck',
            'type' => 'mothership-payment',
            'params' => '{"display_name":"Pay By Check","checkpayee":"Test Payee"}',
        ]);
    }

    public function testGetFeeIsAlwaysZero()
    {
        $this->assertSame(0.0, $this->plugin->getFee(0));
        $this->assertSame(0.0, $this->plugin->getFee(175.00));
        $this->assertSame(0.0, $this->plugin->getFee(10000));
    }

    public function testDisplayFee()
    {
        $this->assertSame('No Fee', $this->plugin->displayFee(175.00));
    }

    public function testInitiateWithoutAnInvoiceReturnsFalse()
    {
        $payment = (object) ['id' => 1];

        $this->assertFalse($this->plugin->initiate($payment, null));
    }
}
