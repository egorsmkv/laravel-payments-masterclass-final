<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Payment;

class MonitoringPaymentsController extends Controller
{
    /**
     * Show all payments.
     *
     * @param Request $request
     *
     * @return View
     */
    public function index(Request $request): View
    {
        $this->initialize($request);

        $payments = Payment::query()->orderByDesc('id')->get();

        return view('monitoring.index', compact('payments'));
    }

    /**
     * Show a payment.
     *
     * @param Request $request
     *
     * @return View
     */
    public function showPayment(Request $request, string $id): View
    {
        $this->initialize($request);

        /** @var Payment|null $payment */
        $payment = Payment::query()->where(['id' => $id])->first();
        if (!$payment) {
            abort(404);
        }

        return view('monitoring.show_payment', compact('payment'));
    }
}
