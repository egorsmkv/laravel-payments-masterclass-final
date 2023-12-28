@extends('layouts.app')

@section('title', __('main.index'))

@section('content')

    <div class="jumbotron text-center">
        <h2>Welcome to our app 🚀</h2>
    </div>

    <div class="jumbotron">
        <form action="{{ route('handle_payment_form') }}" method="post">

            @csrf

            <label for="payment-system-field">Payment System</label>
            <select name="payment_system" class="form-control" id="payment-system-field">
                <option value="liqpay">LiqPay</option>
                <option value="wayforpay">WayForPay</option>
                <option value="fondy">Fondy</option>
            </select>

            <label for="amount-to-pay-field" class="mt-2">Amount to pay</label>
            <input type="text" name="amount_to_pay" class="form-control" id="amount-to-pay-field">

            <input type="submit" class="btn btn-success mt-2" value="Pay">

        </form>
    </div>

@endsection
