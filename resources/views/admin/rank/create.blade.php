@extends('admin.layouts.master')
@section('title')
    Thêm mới cấp độ
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('rank.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách cấp độ
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon warning"><i class="fas fa-crown"></i></span>
                Tạo cấp độ mới
            </h1>
            <p class="page-subtitle">Cấu hình các chỉ số tài chính, số lượng đơn hàng và hạn mức rút tiền cho cấp bậc mới</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('rank.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-body-modern">
                <div class="row">
                    {{-- Column 1: Cấu hình cơ bản & Tài chính --}}
                    <div class="col-12 col-lg-6">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-medal"></i> Thông tin cơ bản & Đơn hàng
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="name">
                                    Tên cấp độ <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                       value="{{ old('name', '') }}"
                                       class="form-control-modern @error('name') is-invalid @enderror"
                                       placeholder="Ví dụ: VIP 1, Silver, Gold..." required>
                                @error('name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern" for="upgrade_fee">
                                            Phí nâng cấp ($) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" step="0.01" name="upgrade_fee" id="upgrade_fee"
                                               value="{{ old('upgrade_fee', 0) }}"
                                               class="form-control-modern @error('upgrade_fee') is-invalid @enderror"
                                               placeholder="0.00" required>
                                        @error('upgrade_fee')
                                            <span class="form-error-modern">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern" for="commission_percentage">
                                            Hoa hồng (%) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" step="0.01" name="commission_percentage" id="commission_percentage"
                                               value="{{ old('commission_percentage', 0) }}"
                                               class="form-control-modern @error('commission_percentage') is-invalid @enderror"
                                               placeholder="Ví dụ: 0.5" required>
                                        @error('commission_percentage')
                                            <span class="form-error-modern">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern" for="spin_count">
                                            Số lượng đơn hàng <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="spin_count" id="spin_count"
                                               value="{{ old('spin_count', 30) }}"
                                               class="form-control-modern @error('spin_count') is-invalid @enderror"
                                               placeholder="Ví dụ: 30" required>
                                        @error('spin_count')
                                            <span class="form-error-modern">{{ $message }}</span>
                                        @enderror
                                        <span class="form-hint-modern">Số đơn được làm tối đa mỗi vòng quay.</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern" for="value">
                                            Tổng giá trị đơn ($) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" step="0.01" name="value" id="value"
                                               value="{{ old('value', 0) }}"
                                               class="form-control-modern @error('value') is-invalid @enderror"
                                               placeholder="0.00" required>
                                        @error('value')
                                            <span class="form-error-modern">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Column 2: Ảnh huy hiệu & Hạn mức rút tiền --}}
                    <div class="col-12 col-lg-6">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-hand-holding-dollar"></i> Hạn mức rút tiền
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern" for="maximum_number_of_withdrawals">
                                            Số lần rút tối đa / ngày <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="maximum_number_of_withdrawals" id="maximum_number_of_withdrawals"
                                               value="{{ old('maximum_number_of_withdrawals', 1) }}"
                                               class="form-control-modern @error('maximum_number_of_withdrawals') is-invalid @enderror"
                                               min="1" required>
                                        @error('maximum_number_of_withdrawals')
                                            <span class="form-error-modern">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern" for="maximum_withdrawal_amount">
                                            Số tiền rút tối đa / lượt ($) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" step="0.01" name="maximum_withdrawal_amount" id="maximum_withdrawal_amount"
                                               value="{{ old('maximum_withdrawal_amount', 1000) }}"
                                               class="form-control-modern @error('maximum_withdrawal_amount') is-invalid @enderror"
                                               min="0" required>
                                        @error('maximum_withdrawal_amount')
                                            <span class="form-error-modern">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-image"></i> Ảnh huy hiệu cấp độ
                            </div>

                            <div class="image-upload-box">
                                <i class="fas fa-cloud-arrow-up text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-2 font-weight-bold" style="font-size: 0.85rem;">Chọn ảnh biểu tượng cho cấp độ</p>
                                <input type="file" name="image" id="image" accept="image/*" class="form-control-file d-inline-block" style="max-width: 280px;">
                                @error('image')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions-bar">
                <a href="{{ route('rank.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-check"></i> Tạo cấp độ
                </button>
            </div>
        </form>
    </div>

</div>
@endsection