@extends('admin.layouts.master')
@section('title')
Đóng băng đơn hàng
@endsection

@section('style-libs')
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/user/frozen_order.css')
@endsection

@section('script-libs')
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@vite('resources/js/admin/user/frozen_order.js')
<script>
    window.currentPermissionCode = "quan_ly_tat_ca_nguoi_dung";
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
                        @php
                        // Tạo mảng map order_id với spun status từ frozen_orders_detail
                        $frozen_spun_map = [];
                        foreach ($frozen_orders_detail ?? [] as $frozen) {
                            if ($frozen->spun) {
                                $frozen_spun_map[$frozen->order_id] = true;
                            }
                        }
                        @endphp
                        @if (!empty($list_orders) && $list_orders->count() > 0)
                        @foreach ($list_orders as $order)
                        @php
                        $is_frozen = in_array($order->id, $frozen_orders ?? []);
                        $is_current = $order->index == $progress->current_spin;
                        $is_frozen_and_spun = isset($frozen_spun_map[$order->id]);
                        // Thêm current-spin nếu là đơn hàng hiện tại HOẶC đã đóng băng và người dùng đã quay tới
                        $is_current_spin = $is_current || $is_frozen_and_spun;
                        $item_class = $is_frozen ? 'already-frozen' : ($is_current_spin ? 'current-spin' : '');
                        // Nếu đã đóng băng và đã quay tới, vẫn giữ class already-frozen nhưng thêm current-spin
                        if ($is_frozen && $is_frozen_and_spun) {
                            $item_class = 'already-frozen current-spin';
                        }
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
                                                @if ($is_frozen_and_spun)
                                                <span class="badge badge-warning ml-2">
                                                    <i class="fas fa-sync-alt"></i> Đã quay đến đây (đã đóng băng)
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
                                            <span class="text-muted">$</span>
                                        </div>
                                        <div class="price-input-wrapper mt-2">
                                            <label for="commission_{{ $order->id }}" class="mb-0 text-muted" style="min-width: 80px;">
                                                <i class="fas fa-percent"></i> Phần trăm hoa hồng:
                                            </label>
                                            <input
                                                type="number"
                                                name="order_data[{{ $order->id }}][commission_percentage]"
                                                id="commission_{{ $order->id }}"
                                                class="price-input"
                                                placeholder="Nhập % (VD: 5)"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                value="10"
                                                data-default-value="10"
                                                disabled>
                                            <span class="text-muted">%</span>
                                            <input
                                                type="hidden"
                                                name="order_data[{{ $order->id }}][order_id]"
                                                value="{{ $order->id }}">
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
                    <div class="frozen-item {{ $frozen->spun ? 'current-spin' : '' }}">
                        <div class="d-flex flex-row">
                            <div class="pe-3">
                                <img class="order_image" width="100x" height="100px" src="{{ Storage::url($frozen->order->image) }}" alt="">
                                @if (!$frozen->spun)
                                <div class="mt-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-info btn-change-image w-100"
                                        data-frozen-id="{{ $frozen->id }}"
                                        data-order-id="{{ $frozen->order->id }}"
                                        title="Thay ảnh">
                                        <i class="fas fa-image"></i> Thay ảnh
                                    </button>
                                </div>
                                @endif
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
                                        @if (!$frozen->spun)
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
                                        @else
                                        <span class="text-muted">
                                            <i class="fas fa-info-circle"></i> Không thể chỉnh sửa khi người dùng đã quay đến
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="frozen-item-body">
                                    <span class="badge badge-info badge-large">
                                        <i class="fas fa-dollar-sign"></i>
                                        Giá giả: {{ format_money($frozen->custom_price ?? 0) }}$
                                    </span>
                                    <span class="badge badge-success badge-large ml-2">
                                        <i class="fas fa-percent"></i>
                                        Hoa hồng: {{ $frozen->commission_percentage ?? $frozen->order->commission_percentage ?? 0 }}%
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
                                    <div class="col-md-5">
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
                                    <div class="col-md-5">
                                        <label class="font-weight-bold">Phần trăm hoa hồng (%)</label>
                                        <input
                                            type="number"
                                            name="commission_percentage"
                                            class="form-control"
                                            value="{{ $frozen->commission_percentage ?? $frozen->order->commission_percentage ?? '' }}"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            placeholder="Nhập % (VD: 5)">
                                    </div>
                                    <div class="col-md-2">
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

                        <!-- Form thay ảnh -->
                        <div class="change-image-form" id="change-image-form-{{ $frozen->id }}">
                            <form
                                action="{{ route('user.update.frozen.order.image', ['user' => $user->id, 'frozenOrder' => $frozen->id]) }}"
                                method="POST"
                                enctype="multipart/form-data"
                                class="form-change-image">
                                @csrf
                                @method('PUT')
                                <div class="row align-items-end">
                                    <div class="col-md-5">
                                        <label class="font-weight-bold">Hình ảnh cũ</label>
                                        <div class="mb-2">
                                            <img src="{{ Storage::url($frozen->order->image) }}" alt="Ảnh cũ" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="font-weight-bold">Chọn ảnh mới</label>
                                        <input
                                            type="file"
                                            name="image"
                                            class="form-control"
                                            accept="image/*"
                                            required>
                                        <small class="text-muted">Chấp nhận: jpeg, png, jpg, gif, svg, webp (tối đa 2MB)</small>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Lưu
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-cancel-change-image"
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