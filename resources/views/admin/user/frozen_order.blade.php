@extends('admin.layouts.master')
@section('title')
Đóng băng đơn hàng
@endsection

@section('style-libs')
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .order-checkbox {
        margin-right: 10px;
    }

    .order-item {
        padding: 12px;
        border-bottom: 1px solid #e3e6f0;
        transition: all 0.2s;
    }

    .order-item:hover {
        background-color: #f8f9fc;
    }

    .already-frozen {
        background-color: #f8d7da;
        color: #721c24;
        opacity: 0.7;
    }

    .current-spin {
        background-color: #d4edda;
    }

    .price-input-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 5px;
    }

    .price-input {
        width: 150px;
        padding: 5px 10px;
        border: 1px solid #d1d3e2;
        border-radius: 4px;
        font-size: 14px;
    }

    .price-input:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
    }

    .order-content {
        flex: 1;
    }

    .order-info {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .frozen-item {
        padding: 15px;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        margin-bottom: 10px;
        background-color: #fff;
        transition: all 0.2s;
    }

    .frozen-item:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .frozen-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .frozen-item-body {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .frozen-actions {
        display: flex;
        gap: 5px;
    }

    .edit-price-form {
        display: none;
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fc;
        border-radius: 5px;
    }

    .edit-price-form.active {
        display: block;
    }

    .tab-content {
        padding: 20px;
    }

    .nav-tabs .nav-link {
        color: #4e73df;
        font-weight: 600;
    }

    .nav-tabs .nav-link.active {
        background-color: #4e73df;
        color: white;
    }

    .badge-large {
        font-size: 1rem;
        padding: 8px 12px;
    }
</style>
@endsection

@section('script-libs')
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_tat_ca_nguoi_dung";

    document.addEventListener('DOMContentLoaded', function() {
        const selectAllBtn = document.getElementById('select_all');
        const deselectAllBtn = document.getElementById('deselect_all');
        const applyAllPriceBtn = document.getElementById('apply_all_price');
        const globalPriceInput = document.getElementById('global_price');
        const checkboxes = document.querySelectorAll('.order-checkbox:not(:disabled)');
        const form = document.getElementById('form');
        const btnSubmit = document.getElementById('btn_submit');

        // Chọn tất cả
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(cb => {
                    cb.checked = true;
                    togglePriceInput(cb);
                });
            });
        }

        // Bỏ chọn tất cả
        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(cb => {
                    cb.checked = false;
                    togglePriceInput(cb);
                });
            });
        }

        // Áp dụng giá cho tất cả
        if (applyAllPriceBtn) {
            applyAllPriceBtn.addEventListener('click', function() {
                const globalPrice = globalPriceInput.value;
                if (!globalPrice) {
                    alert('Vui lòng nhập giá trước!');
                    return;
                }

                const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    alert('Vui lòng chọn ít nhất một đơn hàng!');
                    return;
                }

                checkedBoxes.forEach(cb => {
                    const orderId = cb.value;
                    const priceInput = document.getElementById(`price_${orderId}`);
                    if (priceInput && !priceInput.disabled) {
                        priceInput.value = globalPrice;
                    }
                });

                alert(`Đã áp dụng giá ${globalPrice}$ cho ${checkedBoxes.length} đơn hàng được chọn`);
            });
        }

        // Toggle input giá
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                togglePriceInput(this);
            });
        });

        function togglePriceInput(checkbox) {
            const orderId = checkbox.value;
            const priceInput = document.getElementById(`price_${orderId}`);
            if (priceInput) {
                priceInput.disabled = !checkbox.checked;
                if (!checkbox.checked) {
                    priceInput.value = '';
                }
            }
        }

        // Submit form đóng băng
        if (btnSubmit) {
            btnSubmit.addEventListener('click', function() {
                const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    alert('Vui lòng chọn ít nhất một đơn hàng!');
                    return;
                }

                let missingPrice = false;
                checkedBoxes.forEach(cb => {
                    const orderId = cb.value;
                    const priceInput = document.getElementById(`price_${orderId}`);
                    if (priceInput && !priceInput.value) {
                        missingPrice = true;
                    }
                });

                if (missingPrice) {
                    if (!confirm('Một số đơn hàng chưa có giá giả. Bạn có muốn tiếp tục không?')) {
                        return;
                    }
                }

                if (confirm(`Bạn có chắc chắn muốn đóng băng ${checkedBoxes.length} đơn hàng?`)) {
                    form.submit();
                }
            });
        }

        // Initialize
        checkboxes.forEach(cb => {
            togglePriceInput(cb);
        });

        // Xử lý nút sửa giá
        document.querySelectorAll('.btn-edit-price').forEach(btn => {
            btn.addEventListener('click', function() {
                const frozenId = this.dataset.frozenId;
                const editForm = document.getElementById(`edit-form-${frozenId}`);
                if (editForm) {
                    editForm.classList.toggle('active');
                }
            });
        });

        // Xử lý nút hủy sửa
        document.querySelectorAll('.btn-cancel-edit').forEach(btn => {
            btn.addEventListener('click', function() {
                const frozenId = this.dataset.frozenId;
                const editForm = document.getElementById(`edit-form-${frozenId}`);
                if (editForm) {
                    editForm.classList.remove('active');
                }
            });
        });

        // Xử lý nút hủy đóng băng
        document.querySelectorAll('.btn-unfreeze').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const orderName = this.dataset.orderName;
                if (!confirm(`Bạn có chắc chắn muốn hủy đóng băng đơn hàng "${orderName}"?`)) {
                    e.preventDefault();
                }
            });
        });

        // Xử lý submit form sửa giá
        document.querySelectorAll('.form-edit-price').forEach(form => {
            form.addEventListener('submit', function(e) {
                const priceInput = this.querySelector('input[name="custom_price"]');
                if (!priceInput.value || parseFloat(priceInput.value) < 0) {
                    e.preventDefault();
                    alert('Vui lòng nhập giá hợp lệ!');
                }
            });
        });
    });
</script>
@endsection

@section('content')
<div class="mb-2 ml-3">
    <a href="{{route('user.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
</div>

<div class="container-fluid">
    <!-- Header Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary fs-5">
                    <i class="fas fa-snowflake"></i> Quản lý đóng băng đơn hàng của
                    <span class="text-danger">{{ $user->full_name }}</span>
                </h6>
                <h6 class="mt-2 font-weight-bold text-danger fs-6">
                    <i class="fas fa-wallet"></i> Số dư hiện tại: {{ format_money($user->balance ?? 0) }}$
                </h6>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="freeze-tab" data-toggle="tab" href="#freeze" role="tab">
                <i class="fas fa-plus-circle"></i> Đóng băng mới
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="frozen-list-tab" data-toggle="tab" href="#frozen-list" role="tab">
                <i class="fas fa-list-alt"></i> Danh sách đã đóng băng
                <span class="badge badge-primary badge-large">{{ $frozen_orders_detail->count() }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        <!-- Tab Đóng băng mới -->
        <div class="tab-pane fade show active" id="freeze" role="tabpanel">
            <form action="{{ route('user.frozen.order', ['user' => $user->id]) }}" method="post" id="form">
                @csrf
                @method('POST')

                <!-- Áp dụng giá chung -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-dollar-sign"></i> Áp dụng giá chung (tùy chọn)</h6>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label for="global_price" class="font-weight-bold">Nhập giá giả chung ($)</label>
                                <input
                                    type="number"
                                    id="global_price"
                                    class="form-control"
                                    placeholder="Ví dụ: 100"
                                    step="0.01"
                                    min="0">
                                <small class="text-muted">Giá này sẽ được áp dụng cho tất cả đơn hàng được chọn</small>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-info" id="apply_all_price">
                                    <i class="fas fa-check-circle"></i> Áp dụng cho các đơn đã chọn
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách đơn hàng -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="font-weight-bold mb-0">
                            <i class="fas fa-list"></i> Chọn đơn hàng và nhập giá
                        </h6>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary" id="select_all">
                                <i class="fas fa-check-square"></i> Chọn tất cả
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="deselect_all">
                                <i class="fas fa-square"></i> Bỏ chọn tất cả
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        @if (!empty($list_orders) && $list_orders->count() > 0)
                        @foreach ($list_orders as $order)
                        @php
                        $is_frozen = in_array($order->id, $frozen_orders ?? []);
                        $is_current = $order->index == $progress->current_spin;
                        $item_class = $is_frozen ? 'already-frozen' : ($is_current ? 'current-spin' : '');
                        @endphp
                        <div class="order-item {{ $item_class }}">
                            <div class="order-info">
                                <input
                                    type="checkbox"
                                    name="order_ids[]"
                                    value="{{ $order->id }}"
                                    class="order-checkbox"
                                    {{ $is_frozen ? 'disabled' : '' }}>
                                <div class="order-content d-flex flex-row">
                                    <div class="pe-3">
                                        <img class="order_image" width="100x" height="100px" src="{{ Storage::url($order->image) }}" alt="">
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>
                                                <strong>#{{ $order->index }}</strong> -
                                                {{ Str::limit($order->name, 60, '...') }}
                                                @if ($is_current)
                                                <span class="badge badge-success ml-2">
                                                    <i class="fas fa-sync-alt"></i> Đã quay đến đây
                                                </span>
                                                @endif
                                                @if ($is_frozen)
                                                <span class="badge badge-danger ml-2">
                                                    <i class="fas fa-lock"></i> Đã đóng băng
                                                </span>
                                                @endif
                                            </span>
                                        </div>

                                        @if (!$is_frozen)
                                        <div class="price-input-wrapper">
                                            <label for="price_{{ $order->id }}" class="mb-0 text-muted" style="min-width: 80px;">
                                                <i class="fas fa-tag"></i> Giá giả:
                                            </label>
                                            <input
                                                type="number"
                                                name="order_data[{{ $order->id }}][custom_price]"
                                                id="price_{{ $order->id }}"
                                                class="price-input"
                                                placeholder="Nhập giá"
                                                step="0.01"
                                                min="0"
                                                disabled>
                                            <input
                                                type="hidden"
                                                name="order_data[{{ $order->id }}][order_id]"
                                                value="{{ $order->id }}">
                                            <span class="text-muted">$</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @else
                        <p class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            Không có đơn hàng nào
                        </p>
                        @endif
                    </div>
                </div>

                <div class="d-flex mt-4 justify-content-center gap-2">
                    <button class="btn btn-success btn-lg" type="button" id="btn_submit">
                        <i class="fas fa-snowflake"></i> Đóng băng các đơn hàng đã chọn
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab Danh sách đã đóng băng -->
        <div class="tab-pane fade" id="frozen-list" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h6 class="font-weight-bold mb-0">
                        <i class="fas fa-snowflake"></i> Các đơn hàng đã đóng băng
                    </h6>
                </div>
                <div class="card-body">
                    @if($frozen_orders_detail->count() > 0)
                    @foreach($frozen_orders_detail as $frozen)
                    <div class="frozen-item">
                        <div class="d-flex flex-row">
                            <div class="pe-3">
                                <img class="order_image" width="100x" height="100px" src="{{ Storage::url($frozen->order->image) }}" alt="">
                            </div>
                            <div class="w-100">
                                <div class="frozen-item-header">
                                    <div>
                                        <h6 class="mb-1">
                                            <strong>#{{ $frozen->order->index }}</strong> -
                                            {{ $frozen->order->name }}
                                            @if ($frozen->spun)
                                            <i class="text-danger fw-bold"> - Người dùng đang mắc kẹt ở đây, đừng sửa giá</i>
                                            @endif
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i>
                                            Đóng băng lúc: {{ $frozen->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
                                    <div class="frozen-actions">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-warning btn-edit-price"
                                            data-frozen-id="{{ $frozen->id }}"
                                            title="Sửa giá">
                                            <i class="fas fa-edit"></i> Sửa giá
                                        </button>
                                        <form
                                            action="{{ route('user.unfrozen.order', ['user' => $user->id, 'frozenOrder' => $frozen->id]) }}"
                                            method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger btn-unfreeze"
                                                data-order-name="{{ $frozen->order->name }}"
                                                title="Hủy đóng băng">
                                                <i class="fas fa-unlock"></i> Hủy đóng băng
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="frozen-item-body">
                                    <span class="badge badge-info badge-large">
                                        <i class="fas fa-dollar-sign"></i>
                                        Giá giả: {{ format_money($frozen->custom_price ?? 0) }}$
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Form sửa giá -->
                        <div class="edit-price-form" id="edit-form-{{ $frozen->id }}">
                            <form
                                action="{{ route('user.update.frozen.order', ['user' => $user->id, 'frozenOrder' => $frozen->id]) }}"
                                method="POST"
                                class="form-edit-price">
                                @csrf
                                @method('PUT')
                                <div class="row align-items-end">
                                    <div class="col-md-6">
                                        <label class="font-weight-bold">Giá giả mới ($)</label>
                                        <input
                                            type="number"
                                            name="custom_price"
                                            class="form-control"
                                            value="{{ $frozen->custom_price }}"
                                            step="0.01"
                                            min="0"
                                            required
                                            placeholder="Nhập giá mới">
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Lưu
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-cancel-edit"
                                            data-frozen-id="{{ $frozen->id }}">
                                            <i class="fas fa-times"></i> Hủy
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-4x mb-3 d-block"></i>
                        <h5>Chưa có đơn hàng nào được đóng băng</h5>
                        <p>Hãy chuyển sang tab "Đóng băng mới" để đóng băng đơn hàng</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection