<?php

namespace Mothership\Component\Mothership\Site\View\Paymentinstructions;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

class HtmlView extends BaseHtmlView
{
    protected $invoice;
    protected $instructions;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();

        // e.g. grabbing 'invoice_id' and 'method' from the request
        $invoiceId = $app->input->getInt('invoice_id', 0);
        $paymentMethod = $app->input->getCmd('paymentmethod', '');

        // Here, you could load an invoice record from your DB or model:
        // $this->invoice = $this->getModel()->getInvoice($invoiceId);
        // For brevity, just build a small object:
        $this->invoice = (object)[
            'id' => $invoiceId,
            'payment_method' => $paymentMethod
        ];

        // Load all 'mothershippayment' plugins
        PluginHelper::importPlugin('mothershippayment');

        // Trigger a custom event that your payment plugins can listen for
        $results = $app->triggerEvent('onMothershipGetPaymentInstructions', [$this->invoice]);

        // Combine or select whichever instructions are relevant
        // For instance, take the first non-empty string:
        foreach ($results as $r)
        {
            if (!empty($r))
            {
                $this->instructions = $r;
                break;
            }
        }

        return parent::display($tpl);
    }
}
