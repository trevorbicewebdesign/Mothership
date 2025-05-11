<?php

namespace Mothership\Plugin\Payment;

defined('_JEXEC') or die;

interface PaymentInterface
{
    public function initiate(array $payment, array $invoice): void;
    public function getFee(float $amount): float;
    public function displayFee(float $amount): string;
}
