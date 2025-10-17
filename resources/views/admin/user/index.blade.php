@extends('admin.layouts.master')
@section('title')
Danh sách người dùng
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/user/index.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@vite('resources/js/admin/user/index.js')
<script>
    window.currentPermissionCode = "quan_ly_tat_ca_nguoi_dung";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card user-card mb-4">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <h6 class="title-header" id="tittle">
                <i class="fas fa-users mr-2"></i>Danh sách người dùng
            </h6>
            <div id="div_btn_create">
                <a id="btn_create" href="{{route('user.create')}}" class="btn btn-create text-decoration-none">
                    <i class="fas fa-plus mr-2"></i>Thêm mới
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Thông tin</th>
                            <th>Ngân hàng</th>
                            <th>Trạng thái</th>
                            <th>Lịch sử</th>
                            <th style="width: 180px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        @if (!empty($users))
                        @foreach ($users as $index =>$item)
                        @php
                        $should_show_button = true;
                        $frozen_order_id = null;
                        if (!empty($item->frozen_orders)) {
                            foreach ($item->frozen_orders as $frozen_order) {
                                if ($frozen_order->custom_price !== null && $frozen_order->is_frozen == true) {
                                    $should_show_button = false;
                                    $frozen_order_id=$frozen_order->id;
                                    break;
                                }
                            }
                        }
                        @endphp
                        <tr>
                            <td class="text-center">
                                <span class="badge badge-primary-modern text-dark">{{$index+1}}</span>
                            </td>
                            <td>
                                <div class="info-box">
                                    <div class="mb-2">
                                        <span class="info-label">ID:</span> 
                                        <span class="info-value">{{ $item->id }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="info-label">Họ và tên:</span> 
                                        <a class="user-link" href="{{ route('user.edit',['user'=>$item->id]) }}">
                                            {{Str::limit($item->full_name, 30, '...')}}
                                        </a>
                                    </div>
                                    <div class="mb-2">
                                        <span class="info-label">Username:</span> 
                                        <span class="info-value">{{ $item->username }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="info-label">SĐT:</span> 
                                        <span class="info-value">{{ $item->phone }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="info-label">Email:</span> 
                                        <span class="info-value">{{ $item->email ?: "Chưa có" }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="info-label">Số dư:</span> 
                                        <span class="balance-highlight">{{ format_money($item->balance) }}$</span>
                                    </div>
                                    @if (!empty($item->referrer))
                                    <div class="mb-2">
                                        <span class="info-label">Giới thiệu bởi:</span> 
                                        <span class="info-value">{{ $item->referrer->full_name }} ({{ $item->referrer->username }})</span>
                                    </div>
                                    @endif
                                    <div>
                                        <span class="info-label">Cấp bậc:</span> 
                                        <span class="info-value">{!! optional($item->rank)->name ?? '<i class="text-secondary">Chưa có cấp bậc</i>' !!}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="info-box">
                                    <div class="mb-2">
                                        <span class="info-label">Tên TK:</span> 
                                        <span class="info-value">{{ $item->username_bank ?: "Chưa liên kết" }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="info-label">Số TK:</span> 
                                        <span class="info-value">{{ $item->account_number ?: "Chưa liên kết" }}</span>
                                    </div>
                                    <div>
                                        <span class="info-label">Ngân hàng:</span> 
                                        <span class="info-value">{{ $item->bank_name ?: "Chưa liên kết" }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column align-items-start">
                                    @if($item->status=="activated")
                                    <span class="badge-modern badge-success-modern">
                                        <i class="fas fa-check-circle mr-1"></i>Đã kích hoạt
                                    </span>
                                    @elseif($item->status=="inactivated")
                                    <span class="badge-modern badge-warning-modern">
                                        <i class="fas fa-clock mr-1"></i>Chưa kích hoạt
                                    </span>
                                    @else
                                    <span class="badge-modern badge-danger-modern">
                                        <i class="fas fa-lock mr-1"></i>Bị khóa
                                    </span>
                                    @endif
                                    
                                    @if (!$should_show_button)
                                    <span class="badge-modern badge-danger-modern">
                                        <i class="fas fa-snowflake mr-1"></i>Đóng băng
                                    </span>
                                    @endif
                                    
                                    @if ($item->clone_account)
                                    <span class="badge-modern badge-info-modern">
                                        <i class="fas fa-copy mr-1"></i>Clone
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="info-box">
                                    <div class="mb-2">
                                        <span class="info-label"><i class="fas fa-plus-circle mr-1"></i>Tạo:</span><br>
                                        <small class="info-value">{{ $item->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div>
                                        <span class="info-label"><i class="fas fa-edit mr-1"></i>Cập nhật:</span><br>
                                        <small class="info-value">{{ $item->updated_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="action-container">
                                    @if($item->status=="activated")
                                    <a href="{{ route('user.change.status',['user'=>$item->id]) }}" 
                                       class="btn-action btn-danger-modern" 
                                       title="Khóa tài khoản">
                                        <i class="fas fa-lock"></i>
                                    </a>
                                    @elseif($item->status=="inactivated")
                                    <a href="{{ route('user.change.status',['user'=>$item->id]) }}" 
                                       class="btn-action btn-primary-modern"
                                       title="Kích hoạt">
                                        <i class="fas fa-circle-check"></i>
                                    </a>
                                    @else
                                    <a href="{{ route('user.change.status',['user'=>$item->id]) }}" 
                                       class="btn-action btn-success-modern"
                                       title="Mở khóa">
                                        <i class="fas fa-lock-open"></i>
                                    </a>
                                    @endif
                                    
                                    <a href="{{ route('user.frozen.order.interface',['user'=>$item->id]) }}"
                                       class="btn-action btn-dark-modern"
                                       title="Đóng băng đơn hàng">
                                        <i class="fas fa-snowflake"></i>
                                    </a>
                                    
                                    <a href="{{ route('user.edit',['user'=>$item->id]) }}" 
                                       class="btn-action btn-warning-modern"
                                       title="Chỉnh sửa">
                                        <i class="fas fa-pen-to-square"></i>
                                    </a>
                                    
                                    <button class="btn-action btn-success-modern btn_plus_money" 
                                            id="{{ $item->id }}"
                                            title="Thêm tiền">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có người dùng nào!</p>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->
@endsection