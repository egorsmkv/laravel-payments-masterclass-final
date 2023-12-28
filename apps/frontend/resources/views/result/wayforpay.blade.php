@extends('layouts.app')

@section('title', 'Payment result for WayForPay')

@section('content')

    <div class="jumbotron text-center">
        <p>
            Payment result for WayForPay, ID = {{ $payment->id }}
        </p>

        <p>
            Thank you for the payment! Transaction status: {{ $context['transactionStatus'] }}
        </p>

    </div>

@endsection
