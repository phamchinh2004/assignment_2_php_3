<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $brandName = config('app.name', 'Dropshipping');
        $pageTitle = trim(html_entity_decode(strip_tags($__env->yieldContent('title')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $pageDescription = trim(html_entity_decode(strip_tags($__env->yieldContent('description')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }} | {{ $brandName }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }} | {{ $brandName }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/logo/tta.webp') }}">
    <link rel="icon" href="{{ asset('images/logo/tta.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/public-pages.css')
</head>
<body>
    <header class="public-header">
        <div class="public-shell public-header__inner">
            <a class="public-brand" href="{{ route('about') }}" aria-label="{{ $brandName }}">
                <img src="{{ asset('images/logo/tta.webp') }}" alt="{{ $brandName }}">
                <span>{{ $brandName }}</span>
            </a>
            <nav class="public-nav" aria-label="Information navigation">
                <a class="{{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About Us</a>
                <a class="{{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact Us</a>
                <a class="{{ request()->routeIs('privacy') ? 'active' : '' }}" href="{{ route('privacy') }}">Privacy</a>
                <a class="{{ request()->routeIs('terms') ? 'active' : '' }}" href="{{ route('terms') }}">Terms</a>
            </nav>
            @guest
                <a class="public-account-link" href="{{ route('login') }}">Sign in</a>
            @else
                <a class="public-account-link" href="{{ auth()->user()->role === \App\Models\User::ROLE_MEMBER ? route('home') : route('chat-panel') }}">Open account</a>
            @endguest
        </div>
    </header>

    <main class="public-main">
        <div class="public-shell">
            <div class="public-page-heading">
                <span class="public-eyebrow">{{ $brandName }}</span>
                <h1>@yield('heading')</h1>
                <p>@yield('lead')</p>
                <span class="public-updated">Last updated: October 1, 2026</span>
            </div>
            <article class="public-content">
                @yield('content')
            </article>
        </div>
    </main>

    <footer class="public-footer">
        <div class="public-shell public-footer__inner">
            <div>
                <strong>{{ $brandName }}</strong>
                <p>An independent platform for product discovery, order management, account activity, and customer support.</p>
            </div>
            <nav aria-label="Website policies">
                <a href="{{ route('about') }}">About Us</a>
                <a href="{{ route('contact') }}">Contact Us</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('terms') }}">Terms &amp; Conditions</a>
                <a href="{{ route('payment_refund_policy') }}">Payment &amp; Refund Policy</a>
            </nav>
        </div>
        <div class="public-shell public-footer__notice">
            {{ $brandName }} is an independent platform and is not an official login portal for TikTok, TikTok Shop, Google, Facebook, or any other third-party service.
        </div>
    </footer>
</body>
</html>
