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
                        <tr id="user-{{ $item->id }}" class="user-row">
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
                                    <a href="{{ route('chat-panel') }}#user-{{ $item->id }}" 
                                       class="btn-action btn-info-modern"
                                       title="Nhắn tin">
                                        <i class="fas fa-comment-dots"></i>
                                    </a>
                                    
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

<!-- Modal Cộng Tiền -->
<div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content deposit-modal">
            <div class="modal-header-gradient">
                <h5 class="modal-title" id="depositModalLabel">
                    <i class="fas fa-wallet me-2"></i>Nạp tiền cho khách hàng
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- User Info -->
                <div class="user-info-card mb-4">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 user-name" id="modalUserName">---</h6>
                            <p class="mb-0 text-muted small">
                                <span id="modalUserUsername">---</span> • 
                                Số dư hiện tại: <strong class="text-primary" id="modalUserBalance">0$</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Amount Input -->
                <div class="mb-4">
                    <label for="depositAmount" class="form-label fw-semibold">
                        <i class="fas fa-dollar-sign text-success me-1"></i>Số tiền nạp (USD)
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-money-bill-wave text-success"></i>
                        </span>
                        <input type="number" 
                               class="form-control form-control-lg" 
                               id="depositAmount" 
                               placeholder="Nhập số tiền (VD: 100)" 
                               min="0" 
                               step="0.01"
                               autocomplete="off">
                        <span class="input-group-text bg-light">$</span>
                    </div>
                    <div class="mt-2 amount-preview" id="amountPreview"></div>
                </div>

                <!-- Deposit Type Toggle -->
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-3">
                        <i class="fas fa-tags text-info me-1"></i>Loại nạp tiền
                    </label>
                    <div class="deposit-type-toggle">
                        <input type="radio" class="btn-check" name="depositType" id="depositTypeReal" value="real" checked>
                        <label class="btn btn-outline-success" for="depositTypeReal">
                            <i class="fas fa-credit-card me-2"></i>
                            <div>
                                <div class="fw-bold">Tiền nạp thực</div>
                                <small class="d-block">Khách hàng nạp</small>
                            </div>
                        </label>

                        <input type="radio" class="btn-check" name="depositType" id="depositTypeBonus" value="bonus">
                        <label class="btn btn-outline-warning" for="depositTypeBonus">
                            <i class="fas fa-gift me-2"></i>
                            <div>
                                <div class="fw-bold">Tiền thưởng</div>
                                <small class="d-block">Hệ thống tặng</small>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="summary-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Số dư hiện tại:</span>
                        <strong id="summaryCurrentBalance">0$</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Số tiền nạp:</span>
                        <strong class="text-success" id="summaryDepositAmount">+0$</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Số dư sau nạp:</span>
                        <strong class="text-primary fs-5" id="summaryNewBalance">0$</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Hủy
                </button>
                <button type="button" class="btn btn-success-gradient px-4" id="confirmDepositBtn">
                    <i class="fas fa-check me-2"></i>Xác nhận nạp tiền
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirm-modal">
            <div class="modal-body text-center p-4">
                <div class="confirm-icon mb-3">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h5 class="mb-3 fw-bold" id="confirmTitle">Xác nhận nạp tiền</h5>
                <p class="text-muted mb-4" id="confirmMessage">Bạn chắc chắn muốn thực hiện?</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Hủy
                    </button>
                    <button type="button" class="btn btn-primary-gradient px-4" id="confirmYesBtn">
                        <i class="fas fa-check me-2"></i>Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thành công -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content success-modal">
            <div class="modal-body text-center p-4">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h5 class="mb-3 fw-bold text-success">Thành công!</h5>
                <p class="text-muted mb-4" id="successMessage">Nạp tiền thành công</p>
                <button type="button" class="btn btn-success-gradient px-4" id="successOkBtn">
                    <i class="fas fa-check me-2"></i>Đồng ý
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lỗi -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content error-modal">
            <div class="modal-body text-center p-4">
                <div class="error-icon mb-3">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h5 class="mb-3 fw-bold text-danger">Lỗi!</h5>
                <p class="text-muted mb-4" id="errorMessage">Có lỗi xảy ra</p>
                <button type="button" class="btn btn-danger-gradient px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Đóng
                </button>
            </div>
        </div>
    </div>
</div>

@endsection