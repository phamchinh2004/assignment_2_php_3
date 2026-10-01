@extends('public.layout')

@section('title', 'About Us')
@section('description', 'Learn about the platform, its purpose, and the transparency commitments of ' . config('app.name', 'Dropshipping') . '.')
@section('heading', 'About Us')
@section('lead', 'We operate an independent platform where users can manage their accounts, discover products, track orders, review account activity, and access customer support in one place.')

@section('content')
    <section>
        <h2>What we provide</h2>
        <p>{{ config('app.name', 'Dropshipping') }} provides tools for managing account information, personal details, orders, transactions, referral activity, and communication with customer support.</p>
        <p>Balance information, transaction status, order status, and related account activity are displayed directly in the user account so users can review their records.</p>
    </section>

    <section>
        <h2>Brand transparency</h2>
        <p>{{ config('app.name', 'Dropshipping') }} is an independent platform. Third-party names, logos, products, or trademarks that may appear on the website are used only for identification or reference purposes.</p>
        <p>This website is not TikTok, TikTok Shop, ByteDance, Google, Facebook, or an official login portal for those services. We do not claim sponsorship, endorsement, or ownership by those brands unless explicitly stated.</p>
    </section>

    <section>
        <h2>Our principles</h2>
        <div class="public-grid">
            <div class="public-info-card"><strong>Transparency</strong><span>Important information about accounts, transactions, and policies is presented clearly and made accessible to users.</span></div>
            <div class="public-info-card"><strong>Account security</strong><span>Users should use credentials created specifically for their account on this platform and should never submit third-party account passwords.</span></div>
            <div class="public-info-card"><strong>Support</strong><span>Users can contact customer support when they need help reviewing an account, order, or transaction issue.</span></div>
        </div>
    </section>
@endsection
