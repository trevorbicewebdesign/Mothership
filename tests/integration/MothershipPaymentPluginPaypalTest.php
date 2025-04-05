<?php

namespace Tests\Integration;

use \Tests\Support\IntegrationTester;
use \PlgMothershipPaymentPaypal;

class MothershipPaymentPluginPaypalTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected $clientData;
    protected $accountData;
    protected $invoiceData;
    protected $invoiceItemData = [];

    protected function _before()
    {
        require_once JPATH_ROOT . '/administrator/components/com_mothership/src/Helper/InvoiceHelper.php';
        require_once JPATH_ROOT . '/plugins/mothership-payment/paypal/paypal.php';

        $this->clientData = $this->tester->createMothershipClient([
            'name' => 'Test Client',
            'email' => 'test.smith@mailinator.com',
            'owner_user_id' => 1,
        ]);

        $this->accountData = $this->tester->createMothershipAccount([
            'client_id' => $this->clientData['id'],
            'name' => 'Test Account',
        ]);

        $this->invoiceData = $this->tester->createMothershipInvoice([
            'client_id' => $this->clientData['id'],
            'account_id' => $this->accountData['id'],
            'total' => '175.00',
            'number' => 1001,
            'due_date' => NULL,
            'created' => date('Y-m-d H:i:s'),
            'status' => 1,
        ]);

        $this->invoiceItemData[] = $this->tester->createMothershipInvoiceItem([
            'invoice_id' => $this->invoiceData['id'],
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'hours' => '1',
            'minutes' => '30',
            'quantity' => '1.5',
            'rate' => '70.00',
            'subtotal' => '105.00',
        ]);

        $this->invoiceItemData[] = $this->tester->createMothershipInvoiceItem([
            'invoice_id' => $this->invoiceData['id'],
            'name' => 'Test Item 2',
            'description' => 'Test Description 2',
            'hours' => '1',
            'minutes' => '0',
            'quantity' => '1',
            'rate' => '70.00',
            'subtotal' => '70.00',
        ]);
    }

    public function testGetPaypalRedirectUrlSuccess()
    {
        $invoice_id = $this->invoiceData['id'];
        $number = '1001';
        $amount = "100.00";
       
        $PlgMothershipPaymentPaypal = $this->make(PlgMothershipPaymentPaypal::class, [
            'dispatcher' => null,
            'getDomain' => 'https://joomlav4.trevorbice.com/',
            'getParam' => function($name, $default) {
                if($name == 'sandbox') {
                    return 0;
                }
                if($name == 'paypal_email') {
                    return 'test.smith@paypal.com';
                }
                return $default;
            }
        ]);
        $results = $PlgMothershipPaymentPaypal->getPaypalRedirectUrl($invoice_id, $amount);
        codecept_debug($results);

        $domain = 'https://joomlav4.trevorbice.com/';

        $paypalData = [
            'cmd' => '_xclick',
            'business' => 'test.smith@paypal.com',
			'custom' => $invoice_id,
            'item_name' => "Invoice {$number}",
			'amount' => '104.12',
			'currency_code' => "USD",
            'no_shipping' => 1,
            'cancel_return' => "{$domain}index.php?option=com_mothership&view=invoices",
            'notify_url' => "{$domain}index.php?option=com_mothership&paypal_notify=1&invoice={$invoice_id}",
            'return' => "{$domain}index.php?option=com_mothership&view=invoices",
        ];

        $expected = "https://www.paypal.com/cgi-bin/webscr?" . http_build_query($paypalData);
        $this->assertEquals($expected, $results);

    }

    public function testGetPaypalRedirectUrlInvalidDomain()
    {
        $invoice_id = 1;
        $amount = "100.00";
       
        $PlgMothershipPaymentPaypal = $this->make(PlgMothershipPaymentPaypal::class, [
            'dispatcher' => null,
            'getDomain' => '',
            'getParam' => function($name, $default) {
                if($name == 'sandbox') {
                    return 0;
                }
                if($name == 'paypal_email') {
                    return 'test.smith@paypal.com';
                }
                return $default;
            }
        ]);
        try {
            $results = $PlgMothershipPaymentPaypal->getPaypalRedirectUrl($invoice_id, $amount);
            codecept_debug($results);
        } catch (\Exception $e) {
            $this->assertEquals('Invalid domain', $e->getMessage());
            return;
        }
    }

    public function testGetPaypalRedirectUrlInvalidAmount()
    {
        $invoice_id = 1;
        $amount = "0";
       
        $PlgMothershipPaymentPaypal = $this->make(PlgMothershipPaymentPaypal::class, [
            'dispatcher' => null,
            'getDomain' => 'https://joomlav4.trevorbice.com/',
            'getParam' => function($name, $default) {
                if($name == 'sandbox') {
                    return 0;
                }
                if($name == 'paypal_email') {
                    return 'test.smith@paypal.com';
                }
                return $default;
            }
        ]);
        try {
            $results = $PlgMothershipPaymentPaypal->getPaypalRedirectUrl($invoice_id, $amount);
            codecept_debug($results);
        } catch (\Exception $e) {
            $this->assertEquals('Invalid amount', $e->getMessage());
            return;
        }
    }

    public function testGetPaypalRedirectUrlInvalidInvoice()
    {
        $invoice_id = 10;
        $amount = "100.00";
       
        $PlgMothershipPaymentPaypal = $this->make(PlgMothershipPaymentPaypal::class, [
            'dispatcher' => null,
            'getDomain' => 'https://joomlav4.trevorbice.com/',
            'getParam' => function($name, $default) {
                if($name == 'sandbox') {
                    return 0;
                }
                if($name == 'paypal_email') {
                    return 'test.smith@paypal.com';
                }
                return $default;
            }
        ]);
        try {
            $results = $PlgMothershipPaymentPaypal->getPaypalRedirectUrl($invoice_id, $amount);
            codecept_debug($results);
        } catch (\Exception $e) {
            $this->assertEquals('Invoice ID 10 not found.', $e->getMessage());
            return;
        }
    }
}