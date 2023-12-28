<?php

namespace App\Helpers;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use LiqPay;
use WayForPay\SDK\Collection\ProductCollection;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Domain\Product;
use WayForPay\SDK\Wizard\PurchaseWizard;
use Exception;

class PaymentHelper
{
    /**
     * @throws \Exception
     */
    public static function generateHTML(Payment $payment)
    {
        switch ($payment->payment_system) {
            case 'liqpay':
                return self::liqPayHandler($payment);
            case 'wayforpay':
                return self::wayForPayHandler($payment);
            case 'fondy':
                return self::fondyHandler($payment);
            default:
                throw new \Exception('unexpected payment system');
        }
    }

    public static function liqPayHandler(Payment $payment)
    {
        $publicKey = config('payment_systems.liqpay.public_key');
        $privateKey = config('payment_systems.liqpay.private_key');

        $liqpay = new LiqPay($publicKey, $privateKey);

        return $liqpay->cnb_form([
            'action' => 'pay',
            'language' => 'uk',
            'amount' => $payment->amount_to_pay,
            'currency' => 'UAH',
            'description' => sprintf('Payment of ID=%d', $payment->id),
            'order_id' => sprintf('order_id_%d', $payment->id),
            'version' => '3',
            'result_url' => route('result', ['paymentSystem' => 'liqpay', 'orderID' => $payment->id]),
            'server_url' => route('webhook_liqpay'),
        ]);
    }

    public static function wayForPayHandler(Payment $payment)
    {
        $account = config('payment_systems.wayforpay.login');
        $secret = config('payment_systems.wayforpay.secret_key');

        $credential = new AccountSecretCredential($account, $secret);

        return PurchaseWizard::get($credential)
            ->setOrderReference(sprintf('order_id_%d', $payment->id))
            ->setAmount($payment->amount_to_pay)
            ->setCurrency('UAH')
            ->setOrderDate(new \DateTime())
            ->setMerchantDomainName(env('SITE_DOMAIN'))
            ->setProducts(new ProductCollection([
                new Product('Test product', $payment->amount_to_pay, 1)
            ]))
            ->setReturnUrl(route('result', ['paymentSystem' => 'wayforpay', 'orderID' => $payment->id]))
            ->setServiceUrl(route('webhook_wayforpay'))
            ->getForm()
            ->getAsString();
    }

    public static function fondyHandler(Payment $payment)
    {
        $merchantID = config('payment_systems.fondy.merchant_id');
        $secretKey = config('payment_systems.fondy.secret_key');

        \Cloudipsp\Configuration::setMerchantId($merchantID);
        \Cloudipsp\Configuration::setSecretKey($secretKey);
        \Cloudipsp\Configuration::setApiVersion('1.0');
        \Cloudipsp\Configuration::setRequestType('json');

        try {
            $data = [
                'order_desc' => sprintf('Payment of ID=%d', $payment->id),
                'currency' => 'UAH',
                'amount' => $payment->amount_to_pay * 100,
                'default_payment_system' => 'card',
                'response_url' => route('result', ['paymentSystem' => 'fondy', 'orderID' => $payment->id]),
                'server_callback_url' => route('webhook_fondy'),
                'merchant_data' => [
                    'fields' => [
                        [
                            'label' => 'Order ID',
                            'name' => 'order_id',
                            'value' => $payment->id,
                            'readonly' => true,
                            'required' => true,
                            'valid' => [
                                'pattern' => 'd+'
                            ]
                        ],
                    ]
                ]
            ];

            // $url = \Cloudipsp\Checkout::button($data);
            // return view('blocks.fondy_pay_button', compact('url'));

            return \Cloudipsp\Checkout::form($data);
        } catch (Exception $e) {
            Log::error('Error when create Fondy button', ['exc' => $e]);
        }

        return '-';
    }
}
