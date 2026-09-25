@extends('admin.layouts.master')
@section('title')
    Lịch sử nạp tiền
@endsection

@section('style-libs')
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('script-libs')
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
    @vite('resources/js/admin/transaction/deposit.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = $('#dataTable').DataTable();
            const filterBtns = document.querySelectorAll('.filter-tab-btn');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.getAttribute('data-filter') || '';
                    table.search(filter).draw();
                });
            });
        });
    </script>
@endsection

@section('content')
@php
    $authorization = app(\App\Services\AuthorizationService::class);
    $canChangeDepositType = $authorization->can(auth()->user(), config('authorization.capabilities.deposits_change_type'));
    $canDeleteDeposit = $authorization->can(auth()->user(), config('authorization.capabilities.deposits_delete'));
    $canViewCustomerDetail = $authorization->can(auth()->user(), config('authorization.capabilities.customers_view_detail'));
@endphp
@php
    $totalDeposits = !empty($list_deposit_transactions) ? $list_deposit_transactions->count() : 0;
    $normalDeposits = !empty($list_deposit_transactions) ? $list_deposit_transactions->where('transaction_type', 'normal') : collect();
    $bonusDeposits = !empty($list_deposit_transactions) ? $list_deposit_transactions->where('transaction_type', 'bonus') : collect();

    $totalAmount = !empty($list_deposit_transactions) ? $list_deposit_transactions->sum('value') : 0;
    $normalAmount = $normalDeposits->sum('value');
    $bonusAmount = $bonusDeposits->sum('value');
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon success"><i class="fas fa-arrow-down-to-bracket"></i></span>
                Lịch sử nạp tiền
            </h1>
            <p class="page-subtitle">Theo dõi các khoản tiền được nạp vào tài khoản thành viên (nạp thực & nạp thưởng)</p>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng giao dịch nạp</span>
                <span class="stat-number">{{ number_format($totalDeposits) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-money-bill-wave text-primary"></i> Tổng nạp: {{ format_money($totalAmount, 2) }}$
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-vault"></i>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Tiền nạp thực</span>
                <span class="stat-number text-success">{{ number_format($normalDeposits->count()) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-check-circle text-success"></i> Giá trị: {{ format_money($normalAmount, 2) }}$
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
        </div>

        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Tiền nạp thưởng / Bonus</span>
                <span class="stat-number text-warning">{{ number_format($bonusDeposits->count()) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-gift text-warning"></i> Giá trị: {{ format_money($bonusAmount, 2) }}$
                </span>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-award"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-receipt"></i> Danh sách giao dịch nạp tiền
            </h6>
        </div>

        {{-- Filter Tabs --}}
        <div class="filter-tabs-bar">
            <button type="button" class="filter-tab-btn active" data-filter="">
                <i class="fas fa-layer-group"></i> Tất cả
                <span class="filter-tab-count">{{ $totalDeposits }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Nạp thực">
                <i class="fas fa-check-circle text-success"></i> Tiền nạp thực
                <span class="filter-tab-count">{{ $normalDeposits->count() }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Nạp thưởng">
                <i class="fas fa-gift text-warning"></i> Tiền nạp thưởng
                <span class="filter-tab-count">{{ $bonusDeposits->count() }}</span>
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Khách hàng</th>
                            <th>Người nạp tiền (Staff)</th>
                            <th>Biến động số dư</th>
                            <th>Số dư hiện tại</th>
                            <th class="text-center">Loại giao dịch</th>
                            <th>Thời gian nạp</th>
                            <th class="text-center" style="width: 140px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($list_deposit_transactions))
                            @foreach ($list_deposit_transactions as $index => $item)
                                @php($transactionUser = $item->user)
                                <tr>
                                    {{-- STT --}}
                                    <td class="text-center">
                                        <span class="id-chip">#{{ $index + 1 }}</span>
                                    </td>

                                    {{-- Khách hàng --}}
                                    <td>
                                        @if($transactionUser)
                                            <div class="entity-identity-cell">
                                                <div class="user-avatar-circle" style="width: 38px; height: 38px; font-size: 0.85rem;">
                                                    <span>{{ mb_strtoupper(mb_substr($transactionUser->full_name ?: ($transactionUser->username ?: 'U'), 0, 2)) }}</span>
                                                </div>
                                                <div class="entity-details">
                                                    @if ($canViewCustomerDetail)
                                                    <a class="entity-title" href="{{ route('user.show', ['user' => $transactionUser->id]) }}" title="{{ $transactionUser->full_name }}">
                                                        {{ Str::limit($transactionUser->full_name ?: 'Chưa đặt tên', 24, '...') }}
                                                    </a>
                                                    @else
                                                    <span class="entity-title" title="{{ $transactionUser->full_name }}">{{ Str::limit($transactionUser->full_name ?: 'Chưa đặt tên', 24, '...') }}</span>
                                                    @endif
                                                    <span class="entity-subtitle">
                                                        <span>@<span>{{ $transactionUser->username }}</span></span> • {{ $transactionUser->phone ?: '—' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted font-italic">Tài khoản đã xóa</span>
                                        @endif
                                    </td>

                                    {{-- Người nạp tiền --}}
                                    <td>
                                        <span class="text-dark font-weight-bold" style="font-size: 0.85rem;">
                                            <i class="fas fa-user-tie text-muted mr-1"></i>{{ $item->byUser->username ?? 'Hệ thống' }}
                                        </span>
                                    </td>

                                    {{-- Biến động số dư --}}
                                    <td>
                                        <div class="d-flex flex-column" style="gap: 2px;">
                                            <span class="font-weight-bold text-success" style="font-size: 1rem;">
                                                +{{ format_money($item->value, 2) }}$
                                            </span>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                Trước nạp: {{ format_money($item->initial_balance, 2) }}$
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Số dư hiện tại --}}
                                    <td>
                                        <strong class="text-primary" style="font-size: 0.95rem;">
                                            {{ format_money($transactionUser->balance ?? 0, 2) }}$
                                        </strong>
                                    </td>

                                    {{-- Loại giao dịch --}}
                                    <td class="text-center">
                                        @if($item->transaction_type === 'normal')
                                            <span class="badge-status-modern success">Nạp thực</span>
                                        @elseif($item->transaction_type === 'bonus')
                                            <span class="badge-status-modern warning">Nạp thưởng</span>
                                        @else
                                            <span class="badge-status-modern secondary">{{ $item->transaction_type }}</span>
                                        @endif
                                    </td>

                                    {{-- Thời gian --}}
                                    <td>
                                        <div class="d-flex flex-column text-muted" style="font-size: 0.78125rem;">
                                            <span>{{ $item->created_at ? $item->created_at->format('d/m/Y') : '—' }}</span>
                                            <span style="font-size: 0.7rem;">{{ $item->created_at ? $item->created_at->format('H:i:s') : '' }}</span>
                                        </div>
                                    </td>

                                    {{-- Thao tác --}}
                                    <td class="text-center">
                                        <div class="action-btn-group justify-content-center">
                                            @if($canChangeDepositType && $item->transaction_type === 'normal')
                                                <a href="{{ route('change.deposit.transaction.type', ['transaction' => $item->id]) }}"
                                                   class="btn btn-sm btn-outline-warning"
                                                   style="font-size: 11px; padding: 3px 8px; border-radius: 6px;"
                                                   title="Đổi thành GD Thưởng">
                                                    <i class="fas fa-gift mr-1"></i> Sang Thưởng
                                                </a>
                                            @elseif($canChangeDepositType && $item->transaction_type === 'bonus')
                                                <a href="{{ route('change.deposit.transaction.type', ['transaction' => $item->id]) }}"
                                                   class="btn btn-sm btn-outline-success"
                                                   style="font-size: 11px; padding: 3px 8px; border-radius: 6px;"
                                                   title="Đổi thành GD Thực">
                                                    <i class="fas fa-check-circle mr-1"></i> Sang Thực
                                                </a>
                                            @endif

                                            @if ($canDeleteDeposit)
                                            <form action="{{ route('destroy.deposit', ['transaction' => $item->id]) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('CẢNH BÁO: Xóa giao dịch này sẽ trừ thẳng số tiền khỏi tài khoản người dùng. Tiếp tục?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action-icon delete" title="Xóa giao dịch">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
