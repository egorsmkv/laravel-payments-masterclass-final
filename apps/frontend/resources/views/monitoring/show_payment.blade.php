@extends('layouts.app')

@section('title', 'Payment')

@section('content')

    <p>
        ID: <b>{{ $payment->id }}</b>
    </p>

    <p>
        Payment system: <b>{{ $payment->payment_system }}</b>
    </p>

    <p>
        Amount: <b>{{ $payment->amount_to_pay }}</b>
    </p>

    <p>
        Status: <b>{{ $payment->status }}</b>
    </p>

    <p>
        Logs:
    </p>

    <div class="jumbotron">
        @foreach($payment->getParsedLogs() as $item)
            <div class="mt-2">
                <b>{{ $item['key'] }}</b> : {{ $item['value'] }}
            </div>
        @endforeach
    </div>

@endsection
