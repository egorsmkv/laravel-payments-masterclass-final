<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use App\Models\Payment;
use App\Helpers\PaymentHelper;
use Exception;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Exception\WayForPaySDKException;
use WayForPay\SDK\Handler\ServiceUrlHandler;

class PaymentController extends Controller
{
    /**
     * Handle payment form
     *
     * @param Request $request
     *
     * @return View
     */
    public function handleForm(Request $request): View
    {
        $this->initialize($request);

        $paymentSystem = $request->post('payment_system');
        $amountToPay = $request->post('amount_to_pay');

        $payment = new Payment();
        $payment->payment_system = $paymentSystem;
        $payment->amount_to_pay = $amountToPay;
        $payment->save();

        try {
            $buttonHTML = PaymentHelper::generateHTML($payment);

            return view('show_button', compact('payment', 'buttonHTML'));
        } catch (Exception $e) {
            Log::error('Unknown payment system', ['exc' => $e]);

            abort(500);
        }
    }

    /**
     * The result page after the payment
     *
     * @param Request $request
     * @param string $paymentSystem
     * @param string $orderID
     *
     * @return View
     */
    public function result(Request $request, string $paymentSystem, string $orderID): View
    {
        $this->initialize($request);

        /** @var Payment|null $payment */
        $payment = Payment::query()->where(['id' => $orderID])->first();
        if (!$payment) {
            abort(404);
        }

        if ($paymentSystem !== $payment->payment_system) {
            abort(404);
        }

        $viewName = '';
        $context = [];

        switch ($paymentSystem) {
            case 'liqpay':
                $viewName = 'result.liqpay';
                break;
            case 'wayforpay':
                $viewName = 'result.wayforpay';

                $account = config('payment_systems.wayforpay.login');
                $secret = config('payment_systems.wayforpay.secret_key');

                $credential = new AccountSecretCredential($account, $secret);

                try {
                    $handler = new ServiceUrlHandler($credential);
                    $response = $handler->parseRequestFromGlobals();

                    if ($response->getReason()->isOK()) {
                        $postData = $request->post();
                        $context['transactionStatus'] = $postData['transactionStatus'];
                    } else {
                        Log::error('WayForPay result page error', ['error' => $response->getReason()->getMessage()]);
                        abort(404);
                    }
                } catch (WayForPaySDKException $e) {
                    Log::error('WayForPay SDK exception', ['exc' => $e]);
                    abort(404);
                }

                break;
            case 'fondy':
                $viewName = 'result.fondy';
                break;
        }

        if (empty($viewName)) {
            abort(404);
        }

        return view($viewName, compact('payment', 'context'));
    }
}
