@extends('layouts.app')

@section('title', 'Show button')

@section('content')

    <div class="jumbotron text-center">
        <p>
            Payment for {{ $payment->id }}
        </p>

        <p>
            {!! $buttonHTML !!}
        </p>

    </div>

@endsection
