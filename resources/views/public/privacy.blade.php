@extends('public.layout')

@section('title', 'Privacy Policy')
@section('description', 'Privacy information about account data, location information, transactions, and how ' . config('app.name', 'Dropshipping') . ' processes user information.')
@section('heading', 'Privacy Policy')
@section('lead', 'This policy explains the types of information we may process when you use the website, why that information may be used, and how you can contact us with questions about your data.')

@section('content')
    <section>
        <h2>1. Information we may collect</h2>
        <ul>
            <li>Account information such as your name, username, email address, and phone number.</li>
            <li>Security and access information such as IP address, login time, session data, and information needed to help protect your account.</li>
            <li>Location information such as permission status, coordinates, accuracy, country, or city when a feature that uses location is enabled or used.</li>
            <li>Order, transaction, balance, deposit, withdrawal, balance-history, and referral-program information associated with your account.</li>
            <li>Content you send to customer support, including messages or files that you choose to provide.</li>
        </ul>
    </section>

    <section>
        <h2>2. How we use information</h2>
        <p>We may use information to create and operate accounts, authenticate users, provide website features, display account activity, process account-related actions, provide customer support, prevent abuse, and improve the security and reliability of the service.</p>
    </section>

    <section>
        <h2>3. Cookies and session data</h2>
        <p>The website may use cookies or similar session-storage mechanisms that are necessary to keep users signed in, protect forms, remember appropriate preferences, and operate core features.</p>
    </section>

    <section>
        <h2>4. Service providers and disclosures</h2>
        <p>Information may be processed by infrastructure or technical service providers that are necessary for the website to operate. We may also disclose information when required by law or when reasonably necessary to protect the rights, security, or integrity of users, the service, or other affected parties.</p>
        <p>We do not require you to provide your TikTok, TikTok Shop, Google, Facebook, or other third-party service password in order to use your account on this website.</p>
    </section>

    <section>
        <h2>5. Data retention and security</h2>
        <p>Information may be retained for as long as reasonably necessary for account operation, transaction records, legal obligations, security, and dispute handling. We use technical and administrative measures intended to reduce unauthorized access, loss, misuse, or alteration of data.</p>
    </section>

    <section>
        <h2>6. Your requests</h2>
        <p>You can update certain information from your account and contact customer support if you want to request a review or correction of personal information, or if you have questions about how your data is handled. We may need to verify account ownership before processing certain requests.</p>
    </section>
@endsection
