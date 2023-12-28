<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MonitoringPaymentsController;
use App\Http\Controllers\WebhookPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/monitoring', [MonitoringPaymentsController::class, 'index'])->name('monitoring');
Route::get('/monitoring/payment/{id}', [MonitoringPaymentsController::class, 'showPayment'])
    ->name('monitoring.show_payment');

Route::post('/handle-payment-form', [PaymentController::class, 'handleForm'])->name('handle_payment_form');

Route::any('/result/{paymentSystem}/{orderID}', [PaymentController::class, 'result'])->name('result');

Route::post('/webhook/liqpay', [WebhookPaymentController::class, 'liqpay'])->name('webhook_liqpay');
Route::post('/webhook/wayforpay', [WebhookPaymentController::class, 'wayforpay'])->name('webhook_wayforpay');
Route::post('/webhook/fondy', [WebhookPaymentController::class, 'fondy'])->name('webhook_fondy');
