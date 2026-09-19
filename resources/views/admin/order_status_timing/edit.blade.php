@extends('admin.layouts.master')
@section('title')
    Sửa cấu hình chuyển trạng thái đơn hàng
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('admin.order_status_timing.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách cấu hình
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon warning"><i class="fas fa-pen-ruler"></i></span>
                Sửa bước chuyển: {{ ucfirst($orderStatusTiming->from_status) }} → {{ ucfirst($orderStatusTiming->to_status) }}
            </h1>
            <p class="page-subtitle">Cập nhật thời gian tối thiểu / tối đa và kích hoạt bước chuyển trạng thái này</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form method="POST" action="{{ route('admin.order_status_timing.update', $orderStatusTiming->id) }}">
            @csrf
            @method('PUT')

            <div class="form-body-modern">
                <div class="row">
                    <div class="col-12 col-md-8 mx-auto">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-route"></i> Quy trình chuyển đổi trạng thái
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Trạng thái bắt đầu (Từ)</label>
                                        <input type="text" class="form-control-modern" value="{{ ucfirst($orderStatusTiming->from_status) }}" disabled style="background:#f1f5f9; font-weight:700; color:#3b82f6;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern">Trạng thái đích (Đến)</label>
                                        <input type="text" class="form-control-modern" value="{{ ucfirst($orderStatusTiming->to_status) }}" disabled style="background:#f1f5f9; font-weight:700; color:#10b981;">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern" for="min_time">Thời gian tối thiểu <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control-modern" id="min_time" name="min_time" value="{{ $orderStatusTiming->min_time }}" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern" for="max_time">Thời gian tối đa <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control-modern" id="max_time" name="max_time" value="{{ $orderStatusTiming->max_time }}" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern" for="time_unit">Đơn vị tính <span class="text-danger">*</span></label>
                                        <select class="form-select-modern" id="time_unit" name="time_unit" required>
                                            <option value="minutes" {{ $orderStatusTiming->time_unit == 'minutes' ? 'selected' : '' }}>Phút</option>
                                            <option value="hours" {{ $orderStatusTiming->time_unit == 'hours' ? 'selected' : '' }}>Giờ</option>
                                            <option value="days" {{ $orderStatusTiming->time_unit == 'days' ? 'selected' : '' }}>Ngày</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="description">Mô tả quy trình</label>
                                <textarea class="form-control-modern" id="description" name="description" rows="3" placeholder="Nhập ghi chú hoặc mô tả về bước này...">{{ $orderStatusTiming->description }}</textarea>
                            </div>

                            <div class="form-group-modern">
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" {{ $orderStatusTiming->is_active ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="is_active" style="cursor: pointer;">
                                        Kích hoạt tự động chuyển cho bước này
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions-bar">
                <a href="{{ route('admin.order_status_timing.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-save"></i> Cập nhật cấu hình
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
