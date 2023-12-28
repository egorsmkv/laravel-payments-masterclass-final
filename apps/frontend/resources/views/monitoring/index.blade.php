@extends('layouts.app')

@section('title', 'Monitoring')

@section('content')

    <ul>
    @foreach($payments as $payment)

        <li>
            <a href="{{ route('monitoring.show_payment', $payment->id) }}">#{{ $payment->id }}</a>
        </li>

    @endforeach
    </ul>

@endsection
