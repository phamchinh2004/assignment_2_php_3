@extends('admin.layouts.master')
@section('title')
    Đơn hàng bị báo cáo
@endsection

@section('style-libs')
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('script-libs')
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Danh sách đơn hàng bị báo cáo</h6>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <a class="btn btn-sm {{ $status === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}"
                        href="{{ route('order_reports.index', ['status' => 'pending']) }}">
                        Chờ xử lý
                    </a>
                    <a class="btn btn-sm {{ $status === 'approved' ? 'btn-success' : 'btn-outline-success' }}"
                        href="{{ route('order_reports.index', ['status' => 'approved']) }}">
                        Đã hủy (đơn ảo)
                    </a>
                    <a class="btn btn-sm {{ $status === 'rejected' ? 'btn-secondary' : 'btn-outline-secondary' }}"
                        href="{{ route('order_reports.index', ['status' => 'rejected']) }}">
                        Đã xác nhận (đơn thật)
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>#</th>
                                <th>ID</th>
                                <th>Mã đơn</th>
                                <th>Người đặt</th>
                                <th>Người báo cáo</th>
                                <th>Lý do</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $i => $report)
                                @php
                                    $orderCode = $report->frozenOrder?->order?->order_code ?? 'N/A';
                                    $orderOwner = $report->frozenOrder?->user;
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $report->id }}</td>
                                    <td style="font-weight: 600;">{{ $orderCode }}</td>
                                    <td>
                                        {{ $orderOwner?->full_name ?? $orderOwner?->username ?? ('#' . ($orderOwner?->id ?? 'N/A')) }}
                                    </td>
                                    <td>{{ $report->reporter?->full_name ?? $report->reporter?->username ?? ('#' . $report->reported_by) }}</td>
                                    <td style="max-width: 360px;">
                                        <div style="white-space: normal;">
                                            {{ $report->reason ?: '—' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($report->status === 'pending')
                                            <span class="badge badge-warning">Chờ xử lý</span>
                                        @elseif ($report->status === 'approved')
                                            <span class="badge badge-success">Đã hủy (đơn ảo)</span>
                                        @else
                                            <span class="badge badge-secondary">Đã xác nhận (đơn thật)</span>
                                        @endif
                                    </td>
                                    <td>{{ $report->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-primary"
                                            href="{{ route('order_reports.show', $report) }}">
                                            Xem
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $reports->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection


