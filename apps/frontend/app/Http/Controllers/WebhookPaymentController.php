<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LiqPay;
use App\Helpers\PaymentSignatureHelper;
use Exception;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Exception\WayForPaySDKException;
use WayForPay\SDK\Handler\ServiceUrlHandler;

class WebhookPaymentController extends Controller
{
    /**
     * Webhook for LiqPay
     *
     * @param Request $request
     *
     * @return string
     */
    public function liqpay(Request $request): string
    {
        $postData = $request->post();

        $signature = $postData['signature'];
        $data = $postData['data'];

        Log::info('----');
        Log::info('LiqPay signature', ['data' => $data]);
        Log::info('LiqPay data', ['signature' => $signature]);

        $publicKey = config('payment_systems.liqpay.public_key');
        $privateKey = config('payment_systems.liqpay.private_key');

        $liqpay = new LiqPay($publicKey, $privateKey);
        $parsedData = $liqpay->decode_params($data);
        Log::info('LiqPay parsedData', $parsedData);

        $orderID = Str::replace('order_id_', '', $parsedData['order_id']);
        Log::info('LiqPay extracted order_id', ['order_id' => $orderID]);

        /** @var Payment|null $payment */
        $payment = Payment::query()->where(['id' => $orderID])->first();
        if (!$payment) {
            Log::info('Does not exist payment');
            abort(404);
        }

        try {
            $generatedSignature = PaymentSignatureHelper::generateSignature($payment, ['data' => $postData['data']]);
        } catch (Exception $e) {
            Log::error('Signature generation error', ['exc' => $e]);

            abort(500);
        }

        Log::info('LiqPay generated signature', ['signature' => $generatedSignature]);

        if ($generatedSignature !== $signature) {
            Log::info('Signatures are not equal');
            abort(403);
        }

        $paymentStatus = 'unpaid';
        switch ($parsedData['status']) {
            case 'success':
                $paymentStatus = 'paid';
                break;
        }

        $payment->status = $paymentStatus;
        $payment->payment_system_logs = $parsedData;
        $payment->save();

        Log::info('----');

        return 'OK';
    }

    /**
     * Webhook for WayForPay
     *
     * @param Request $request
     *
     * @return string
     * @throws Exception
     */
    public function wayforpay(Request $request): string
    {
        Log::info('----');

        $account = config('payment_systems.wayforpay.login');
        $secret = config('payment_systems.wayforpay.secret_key');

        $credential = new AccountSecretCredential($account, $secret);
        try {
            $handler = new ServiceUrlHandler($credential);
            $response = $handler->parseRequestFromPostRaw();

            $data = \json_decode(file_get_contents('php://input'), TRUE);
            Log::info('WayForPay post data', $data);

            $orderID = Str::replace('order_id_', '', $data['orderReference']);
            Log::info('WayForPay extracted order_id', ['order_id' => $orderID]);

            /** @var Payment|null $payment */
            $payment = Payment::query()->where(['id' => $orderID])->first();
            if (!$payment) {
                Log::info('Does not exist payment');
                abort(404);
            }

            $status = $response->getTransaction()->getStatus();

            Log::info('WayForPay status', ['status' => $status]);

            $paymentStatus = 'unpaid';
            switch ($status) {
                case 'Approved':
                    $paymentStatus = 'paid';
                    break;
            }

            $payment->status = $paymentStatus;
            $payment->payment_system_logs = $data;
            $payment->save();
        } catch (WayForPaySDKException $e) {
            Log::error('WayForPay SDK exception', ['exc' => $e]);
        }

        Log::info('----');

        return 'OK';
    }

    /**
     * Webhook for Fondy
     *
     * @param Request $request
     *
     * @return string
     * @throws Exception
     */
    public function fondy(Request $request): string
    {
        $merchantID = config('payment_systems.fondy.merchant_id');
        $secretKey = config('payment_systems.fondy.secret_key');

        \Cloudipsp\Configuration::setMerchantId($merchantID);
        \Cloudipsp\Configuration::setSecretKey($secretKey);
        \Cloudipsp\Configuration::setApiVersion('1.0');
        \Cloudipsp\Configuration::setRequestType('json');

        Log::info('----');

        $callbackData = $request->post();
        $result = new \Cloudipsp\Result\Result($callbackData);

        $data = $result->getData();

        Log::info('Fondy post data', $data);

        if (!$result->isValid()) {
            Log::info('Fondy invalid payment');
        } else {
            Log::info('Fondy post data', ['is_approved' => $result->isApproved()]);

            $paymentStatus = 'unpaid';
            if ($result->isApproved()) {
                $paymentStatus = 'paid';
            }

            $orderID = '';
            $merchantData = json_decode($callbackData['merchant_data'], true);

            Log::info('Fondy merchant_data', ['data' => $merchantData]);

            $fields = $merchantData['fields'];

            foreach ($fields as $field) {
                if ($field['name'] == 'order_id') {
                    $orderID = $field['value'];
                }
            }

            if (empty($orderID)) {
                Log::info('OrderID is not found');
                return 'OK';
            }

            Log::info('Fondy extracted order_id', ['order_id' => $orderID]);

            /** @var Payment|null $payment */
            $payment = Payment::query()->where(['id' => $orderID])->first();
            if (!$payment) {
                Log::info('Does not exist payment');
                abort(404);
            }

            $payment->status = $paymentStatus;
            $payment->payment_system_logs = $data;
            $payment->save();
        }

        Log::info('----');

        return 'OK';
    }
}
