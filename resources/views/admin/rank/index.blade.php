@extends('admin.layouts.master')
@section('title')
    Danh sách cấp độ
@endsection

@section('style-libs')
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('script-libs')
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@endsection

@section('content')
@php
    $totalRanks = !empty($rank) ? $rank->count() : 0;
    $maxCommission = !empty($rank) ? $rank->max('commission_percentage') : 0;
    $totalOrdersCreated = !empty($rank) ? $rank->sum('orders_count') : 0;
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon warning"><i class="fas fa-crown"></i></span>
                Quản lý cấp độ (Rank)
            </h1>
            <p class="page-subtitle">Thiết lập các bậc thành viên, phí nâng cấp, hoa hồng và hạn mức quay đơn/rút tiền</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('rank.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-plus"></i>
                <span>Thêm cấp độ mới</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Tổng cấp bậc</span>
                <span class="stat-number text-warning">{{ number_format($totalRanks) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-layer-group text-warning"></i> Cấp độ cấu hình
                </span>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-medal"></i>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Hoa hồng cao nhất</span>
                <span class="stat-number text-success">{{ $maxCommission }}%</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-percent text-success"></i> Tỷ lệ chia thưởng
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-tags"></i>
            </div>
        </div>

        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng đơn hàng tạo theo rank</span>
                <span class="stat-number text-primary">{{ number_format($totalOrdersCreated) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-shopping-cart text-primary"></i> Đơn hàng gắn với rank
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-boxes-stacked"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-list-ol"></i> Danh mục các cấp độ thành viên
            </h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Cấp độ</th>
                            <th>Phí nâng cấp</th>
                            <th>Đơn hàng & Giá trị</th>
                            <th>Hoa hồng</th>
                            <th>Hạn mức rút tiền</th>
                            <th class="text-center">Số đơn đã tạo</th>
                            <th class="text-center" style="width: 120px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($rank))
                            @foreach ($rank as $index => $item)
                                <tr>
                                    <td class="text-center">
                                        <span class="id-chip">#{{ $index + 1 }}</span>
                                    </td>

                                    <td>
                                        <div class="entity-identity-cell">
                                            <div class="entity-thumbnail" style="width: 44px; height: 44px; background: #fffbeb; border-color: #fde68a;">
                                                @if($item->image)
                                                    <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}">
                                                @else
                                                    <i class="fas fa-crown text-warning" style="font-size: 1.2rem;"></i>
                                                @endif
                                            </div>
                                            <div class="entity-details">
                                                <a class="entity-title font-weight-bold" href="{{ route('rank.show', ['rank' => $item->id]) }}">
                                                    {{ $item->name }}
                                                </a>
                                                <span class="entity-subtitle">Cấp ID: {{ $item->id }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <strong class="text-primary" style="font-size: 0.95rem;">
                                            {{ format_money($item->upgrade_fee, 2) }}$
                                        </strong>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-column" style="gap: 2px; font-size: 0.8125rem;">
                                            <span>Số lượng đơn: <b>{{ $item->spin_count }} đơn</b></span>
                                            <span class="text-muted">Tổng giá trị: <b>{{ format_money($item->value, 2) }}$</b></span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge-status-modern success" style="font-size: 0.8rem;">
                                            <i class="fas fa-percent"></i> {{ $item->commission_percentage }}%
                                        </span>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-column" style="gap: 2px; font-size: 0.8125rem;">
                                            <span>Tối đa: <b>{{ $item->maximum_number_of_withdrawals }} lần/ngày</b></span>
                                            <span class="text-muted">Hạn mức/lượt: <b>{{ format_money($item->maximum_withdrawal_amount, 2) }}$</b></span>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="id-chip font-weight-bold">{{ number_format($item->orders_count ?? 0) }}</span>
                                    </td>

                                    <td class="text-center">
                                        <div class="action-btn-group justify-content-center">
                                            <a href="{{ route('rank.show', ['rank' => $item->id]) }}"
                                               class="btn-action-icon view" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('rank.edit', ['rank' => $item->id]) }}"
                                               class="btn-action-icon edit" title="Chỉnh sửa cấp độ">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form action="{{ route('rank.destroy', ['rank' => $item->id]) }}" method="POST"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa cấp độ này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action-icon delete" title="Xóa cấp độ">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
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
