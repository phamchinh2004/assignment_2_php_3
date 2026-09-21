@extends('admin.layouts.master')
@section('title')
    Quản lý rút tiền
@endsection

@section('style-libs')
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('script-libs')
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
    @vite('resources/js/admin/transaction/withdraw.js')
    <script>
        // Filter tabs logic cho DataTable
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
    $totalWithdraws = !empty($list_withdraw_transactions) ? $list_withdraw_transactions->count() : 0;
    $pendingWithdraws = !empty($list_withdraw_transactions) ? $list_withdraw_transactions->where('status', 'processing') : collect();
    $completedWithdraws = !empty($list_withdraw_transactions) ? $list_withdraw_transactions->where('status', 'completed') : collect();
    $cancelledWithdraws = !empty($list_withdraw_transactions) ? $list_withdraw_transactions->where('status', 'cancelled') : collect();

    $totalAmount = !empty($list_withdraw_transactions) ? $list_withdraw_transactions->sum('value') : 0;
    $pendingAmount = $pendingWithdraws->sum('value');
    $completedAmount = $completedWithdraws->sum('value');
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon danger"><i class="fas fa-arrow-up-from-bracket"></i></span>
                Quản lý rút tiền
            </h1>
            <p class="page-subtitle">Kiểm duyệt và xử lý các yêu cầu rút tiền từ tài khoản thành viên</p>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng yêu cầu</span>
                <span class="stat-number">{{ number_format($totalWithdraws) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-receipt text-primary"></i> Tổng số: {{ format_money($totalAmount, 2) }}$
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Chờ xác nhận</span>
                <span class="stat-number text-warning">{{ number_format($pendingWithdraws->count()) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-clock text-warning"></i> Cần duyệt: {{ format_money($pendingAmount, 2) }}$
                </span>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Đã hoàn thành</span>
                <span class="stat-number text-success">{{ number_format($completedWithdraws->count()) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-check-circle text-success"></i> Đã chi: {{ format_money($completedAmount, 2) }}$
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <div class="stat-card-modern danger">
            <div class="stat-content">
                <span class="stat-label">Đã từ chối / Hủy</span>
                <span class="stat-number text-danger">{{ number_format($cancelledWithdraws->count()) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-times-circle text-danger"></i> Đã hoàn trả số dư
                </span>
            </div>
            <div class="stat-icon-wrapper danger">
                <i class="fas fa-ban"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-list"></i> Danh sách yêu cầu rút tiền
            </h6>
        </div>

        {{-- Filter Tabs --}}
        <div class="filter-tabs-bar">
            <button type="button" class="filter-tab-btn active" data-filter="">
                <i class="fas fa-layer-group"></i> Tất cả
                <span class="filter-tab-count">{{ $totalWithdraws }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Chờ xác nhận">
                <i class="fas fa-clock text-warning"></i> Chờ xác nhận
                <span class="filter-tab-count">{{ $pendingWithdraws->count() }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Hoàn thành">
                <i class="fas fa-check-circle text-success"></i> Hoàn thành
                <span class="filter-tab-count">{{ $completedWithdraws->count() }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Đã hủy">
                <i class="fas fa-times-circle text-danger"></i> Đã hủy
                <span class="filter-tab-count">{{ $cancelledWithdraws->count() }}</span>
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Khách hàng</th>
                            <th>Ngân hàng nhận tiền</th>
                            <th>Số tiền rút</th>
                            <th>Số dư hiện tại</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center">Loại GD</th>
                            <th>Duyệt bởi</th>
                            <th>Thời gian</th>
                            <th class="text-center" style="width: 110px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($list_withdraw_transactions))
                            @foreach ($list_withdraw_transactions as $index => $item)
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
                                                    <a class="entity-title" href="{{ route('user.show', ['user' => $transactionUser->id]) }}" title="{{ $transactionUser->full_name }}">
                                                        {{ Str::limit($transactionUser->full_name ?: 'Chưa đặt tên', 22, '...') }}
                                                    </a>
                                                    <span class="entity-subtitle">
                                                        <span>@<span>{{ $transactionUser->username }}</span></span> • {{ $transactionUser->phone ?: '—' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted font-italic">Tài khoản đã xóa</span>
                                        @endif
                                    </td>

                                    {{-- Thông tin Ngân hàng --}}
                                    <td>
                                        <div class="d-flex flex-column" style="gap: 2px;">
                                            <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">
                                                {{ $item->bank_name }}
                                            </span>
                                            <span style="font-family: monospace; font-size: 0.85rem; color: #1e293b; font-weight: 600;">
                                                <i class="fas fa-credit-card text-muted mr-1"></i>{{ $item->account_number }}
                                            </span>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                Chủ TK: <b>{{ $item->username_bank }}</b>
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Số tiền rút --}}
                                    <td>
                                        <div class="d-flex flex-column" style="gap: 2px;">
                                            <span class="font-weight-bold text-danger" style="font-size: 1rem;">
                                                -{{ format_money($item->value, 2) }}$
                                            </span>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                Trước rút: {{ format_money($item->initial_balance, 2) }}$
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Số dư hiện tại --}}
                                    <td>
                                        <strong class="text-primary" style="font-size: 0.95rem;">
                                            {{ format_money($transactionUser->balance ?? 0, 2) }}$
                                        </strong>
                                    </td>

                                    {{-- Trạng thái --}}
                                    <td class="text-center">
                                        @if($item->status === 'processing')
                                            <span class="badge-status-modern warning"><span class="status-dot"></span> Chờ xác nhận</span>
                                        @elseif($item->status === 'completed')
                                            <span class="badge-status-modern success"><span class="status-dot"></span> Hoàn thành</span>
                                        @else
                                            <span class="badge-status-modern danger"><span class="status-dot"></span> Đã hủy</span>
                                        @endif
                                    </td>

                                    {{-- Loại giao dịch --}}
                                    <td class="text-center">
                                        @if($item->transaction_type === 'normal')
                                            <span class="badge-status-modern success" style="font-size: 0.72rem;">Rút thực</span>
                                        @elseif($item->transaction_type === 'virtual_withdraw')
                                            <span class="badge-status-modern secondary" style="font-size: 0.72rem;">Rút ảo</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Duyệt bởi --}}
                                    <td>
                                        <span class="text-muted" style="font-size: 0.8125rem;">
                                            <i class="fas fa-user-check mr-1" style="font-size: 10px;"></i>{{ optional($item->byUser)->username ?? 'Chờ xử lý' }}
                                        </span>
                                    </td>

                                    {{-- Thời gian --}}
                                    <td>
                                        <div class="d-flex flex-column text-muted" style="font-size: 0.78125rem;">
                                            <span>{{ $item->created_at ? $item->created_at->format('d/m/Y') : '—' }}</span>
                                            <span style="font-size: 0.7rem;">{{ $item->created_at ? $item->created_at->format('H:i') : '' }}</span>
                                        </div>
                                    </td>

                                    {{-- Thao tác --}}
                                    <td class="text-center">
                                        @if($item->status === 'processing')
                                            <div class="action-btn-group justify-content-center">
                                                <button class="btn-action-icon view btn_confirm_transaction"
                                                        data-url="{{ route('confirm.withdraw', ['transaction' => $item->id]) }}"
                                                        title="Xác nhận duyệt rút tiền">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn-action-icon delete btn_cancel_transaction"
                                                        data-url="{{ route('cancel.withdraw', ['transaction' => $item->id]) }}"
                                                        title="Từ chối và hoàn tiền">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-muted" style="font-size: 0.75rem;">Đã xử lý</span>
                                        @endif
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
