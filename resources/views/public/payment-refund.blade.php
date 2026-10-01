@extends('public.layout')

@section('title', 'Payment & Refund Policy')
@section('description', 'Information about deposits, withdrawals, order settlement, balance adjustments, and transaction review on ' . config('app.name', 'Dropshipping') . '.')
@section('heading', 'Payment & Refund Policy')
@section('lead', 'This page explains how account-related financial activity is recorded, reviewed, and handled when a transaction discrepancy is reported.')

@section('content')
    <section>
        <h2>1. Transaction information</h2>
        <p>Before taking an action that affects your balance, review the amount, transaction type, and information displayed on screen. The transaction status and balance history shown in your account are the primary records available for reviewing account activity.</p>
    </section>

    <section>
        <h2>2. Deposits</h2>
        <p>Deposits are recorded according to their processing status. If funds have been transferred but your account balance has not been updated, or if the displayed information appears incorrect, contact customer support and provide the relevant transaction information so the issue can be reviewed.</p>
    </section>

    <section>
        <h2>3. Withdrawals</h2>
        <p>Withdrawal requests may require review before completion. The amount, status, and processing information are displayed in your account. You are responsible for checking the receiving-account information before submitting a withdrawal request.</p>
    </section>

    <section>
        <h2>4. Order settlement and balance adjustments</h2>
        <p>Depending on an order's status, the system may record deductions, commissions, settlements, reversals, or other balance adjustments based on the transaction data associated with that order. If an order is cancelled or its status changes, the related financial activity is reflected in the order information and account balance history.</p>
    </section>

    <section>
        <h2>5. Transaction review and refund requests</h2>
        <p>If you believe a transaction was recorded incorrectly, duplicated, or shows an unexpected amount, contact customer support as soon as practical. Each report is reviewed using the transaction history, processing status, and relevant account records available for that event.</p>
        <p>Any refund or balance adjustment, when applicable, depends on the outcome of the review for the specific transaction and will be reflected in the account history.</p>
    </section>
@endsection
