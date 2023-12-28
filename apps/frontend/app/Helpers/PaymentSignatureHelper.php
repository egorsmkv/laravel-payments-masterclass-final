<?php

namespace App\Helpers;

use App\Models\Payment;
use LiqPay;

class PaymentSignatureHelper
{
    /**
     * @throws \Exception
     */
    public static function generateSignature(Payment $payment, array $context = [])
    {
        switch ($payment->payment_system) {
            case 'liqpay':
                return self::liqPayHandler($context);
            case 'wayforpay':
                return self::wayForPayHandler($payment);
            case 'fondy':
                return self::fondyHandler($payment);
            default:
                throw new \Exception('unexpected payment system');
        }
    }

    public static function liqPayHandler(array $context = [])
    {
        $publicKey = config('payment_systems.liqpay.public_key');
        $privateKey = config('payment_systems.liqpay.private_key');

        $liqpay = new LiqPay($publicKey, $privateKey);

        return $liqpay->str_to_sign($privateKey . $context['data'] . $privateKey);
    }

    public static function wayForPayHandler(Payment $payment)
    {
        return '-';
    }

    public static function fondyHandler(Payment $payment)
    {
        return '-';
    }
}
