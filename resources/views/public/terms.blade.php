@extends('public.layout')

@section('title', 'Terms & Conditions')
@section('description', 'Terms governing accounts, orders, transactions, and use of features on ' . config('app.name', 'Dropshipping') . '.')
@section('heading', 'Terms & Conditions')
@section('lead', 'By creating or using an account, you agree to use the platform for its intended purposes, provide appropriate information, and follow the conditions described below.')

@section('content')
    <section>
        <h2>1. User accounts</h2>
        <p>You are responsible for keeping the credentials for your {{ config('app.name', 'Dropshipping') }} account secure and for providing information that is reasonably accurate and current.</p>
        <p>You must not use an account for fraud, impersonation, abuse, or activity that may harm other users, the platform, or related systems.</p>
    </section>

    <section>
        <h2>2. Acceptable use</h2>
        <p>You must not attempt to gain unauthorized access to accounts, data, APIs, or administrative areas; interfere with website operation; distribute malicious code; or use the service for unlawful activity.</p>
    </section>

    <section>
        <h2>3. Orders and transactions</h2>
        <p>Information about orders, amounts, processing status, commissions, and balance changes is displayed in your account. You are responsible for reviewing the information shown before confirming an action that affects your account or balance.</p>
        <p>Additional information about deposits, withdrawals, settlement, and refund review is available in our <a href="{{ route('payment_refund_policy') }}">Payment &amp; Refund Policy</a>.</p>
    </section>

    <section>
        <h2>4. Third-party content and trademarks</h2>
        <p>Third-party names, logos, products, or trademarks may appear for identification or reference purposes. Their presence does not by itself mean that {{ config('app.name', 'Dropshipping') }} is sponsored, owned, operated, or endorsed by that third party.</p>
    </section>

    <section>
        <h2>5. Account restrictions</h2>
        <p>We may restrict certain features or access when we identify abuse, a violation of these terms, a security risk, or a legal requirement. Users may contact customer support to request a review of a specific account issue.</p>
    </section>

    <section>
        <h2>6. Changes to these terms</h2>
        <p>These terms may be updated when features, processes, or applicable requirements change. The current version will be published on this page together with its latest update date.</p>
    </section>
@endsection
