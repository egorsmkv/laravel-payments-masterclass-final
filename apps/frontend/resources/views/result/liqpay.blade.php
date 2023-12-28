@extends('layouts.app')

@section('title', 'Payment result for LiqPay')

@section('content')

    <div class="jumbotron text-center">
        <p>
            Payment result for LiqPay, ID = {{ $payment->id }}
        </p>

        <p>
            Thank you for the payment!
        </p>

    </div>

@endsection
