@extends('public.layout')

@section('title', 'Contact Us')
@section('description', 'Official contact and support channels for ' . config('app.name', 'Dropshipping') . '.')
@section('heading', 'Contact Us')
@section('lead', 'If you need help with your account, an order, or a transaction, please use the official support channels below so we can review the relevant information.')

@section('content')
    <section>
        <h2>Official support channels</h2>
        <div class="public-grid public-grid--contact">
            <div class="public-info-card">
                <strong>In-account customer support</strong>
                <span>Sign in to your account and use the customer support or chat feature to ask for help with account, order, or transaction issues.</span>
                @guest
                    <a href="{{ route('login') }}">Sign in to contact support</a>
                @endguest
            </div>
            @if(filled(config('app.support_email')))
                <div class="public-info-card">
                    <strong>Support email</strong>
                    <span>For easier account verification, contact us from the email address associated with your account when possible.</span>
                    <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>
                </div>
            @endif
        </div>
    </section>

    <section>
        <h2>Security reminder</h2>
        <p>Our support team does not need your TikTok, TikTok Shop, Google, Facebook, or other third-party account passwords to assist with your account on this website.</p>
        <p>Do not send passwords, one-time verification codes, or other sensitive authentication information through unofficial channels.</p>
    </section>
@endsection
