@extends('admin.layouts.master')
@section('title')
    Cấu hình thời gian chuyển trạng thái đơn hàng
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon warning"><i class="fas fa-clock-rotate-left"></i></span>
                Cấu hình thời gian đơn hàng
            </h1>
            <p class="page-subtitle">Thiết lập khoảng thời gian tự động chuyển trạng thái đơn hàng trong hệ thống</p>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius: 10px;">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius: 10px;">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if($timings->isEmpty())
        <div class="card-modern p-4 text-center">
            <div class="empty-state-icon">
                <i class="fas fa-database"></i>
            </div>
            <h5 class="empty-state-title">Chưa có dữ liệu cấu hình!</h5>
            <p class="empty-state-text">Vui lòng chạy migration và seeder để tạo bộ cấu hình thời gian mặc định.</p>
            <div>
                <code>php artisan db:seed --class=OrderStatusTimingSeeder</code>
            </div>
        </div>
    @else
        <div class="card-modern">
            <div class="card-header-modern">
                <h6 class="title-header">
                    <i class="fas fa-sliders"></i> Bảng cấu hình thời gian chuyển tiếp trạng thái
                </h6>
                <span class="text-muted" style="font-size: 0.8125rem;">
                    <i class="fas fa-info-circle text-primary mr-1"></i> Sau khi chỉnh sửa, bấm "Lưu cấu hình" ở cuối bảng
                </span>
            </div>

            <form id="timingForm" method="POST" action="{{ route('admin.order_status_timing.update_multiple') }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-modern" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="width: 240px;">Luồng chuyển trạng thái</th>
                                <th style="width: 140px;">Thời gian tối thiểu</th>
                                <th style="width: 140px;">Thời gian tối đa</th>
                                <th style="width: 130px;">Đơn vị</th>
                                <th>Mô tả quy trình</th>
                                <th class="text-center" style="width: 130px;">Trạng thái</th>
                                <th class="text-center" style="width: 80px;">Sửa lẻ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timings as $timing)
                                <tr>
                                    {{-- Luồng trạng thái --}}
                                    <td>
                                        <div class="d-flex align-items-center" style="gap: 6px;">
                                            <span class="badge-status-modern info" style="font-size: 0.75rem;">
                                                {{ ucfirst($timing->from_status) }}
                                            </span>
                                            <i class="fas fa-arrow-right text-muted" style="font-size: 11px;"></i>
                                            <span class="badge-status-modern success" style="font-size: 0.75rem;">
                                                {{ ucfirst($timing->to_status) }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Min time --}}
                                    <td>
                                        <input type="number"
                                               name="timings[{{ $timing->id }}][min_time]"
                                               value="{{ $timing->min_time }}"
                                               class="form-control-modern"
                                               style="height: 36px;"
                                               min="0"
                                               required>
                                        <input type="hidden" name="timings[{{ $timing->id }}][id]" value="{{ $timing->id }}">
                                    </td>

                                    {{-- Max time --}}
                                    <td>
                                        <input type="number"
                                               name="timings[{{ $timing->id }}][max_time]"
                                               value="{{ $timing->max_time }}"
                                               class="form-control-modern"
                                               style="height: 36px;"
                                               min="0"
                                               required>
                                    </td>

                                    {{-- Đơn vị --}}
                                    <td>
                                        <select name="timings[{{ $timing->id }}][time_unit]" class="form-select-modern" style="height: 36px; padding: 0.25rem 0.5rem;" required>
                                            <option value="minutes" {{ $timing->time_unit == 'minutes' ? 'selected' : '' }}>Phút</option>
                                            <option value="hours" {{ $timing->time_unit == 'hours' ? 'selected' : '' }}>Giờ</option>
                                            <option value="days" {{ $timing->time_unit == 'days' ? 'selected' : '' }}>Ngày</option>
                                        </select>
                                    </td>

                                    {{-- Mô tả --}}
                                    <td>
                                        <input type="text"
                                               name="timings[{{ $timing->id }}][description]"
                                               value="{{ $timing->description }}"
                                               class="form-control-modern"
                                               style="height: 36px;"
                                               placeholder="Mô tả cấu hình">
                                    </td>

                                    {{-- Kích hoạt --}}
                                    <td class="text-center">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox"
                                                   class="custom-control-input"
                                                   id="is_active_{{ $timing->id }}"
                                                   name="timings[{{ $timing->id }}][is_active]"
                                                   {{ $timing->is_active ? 'checked' : '' }}>
                                            <label class="custom-control-label font-weight-bold" for="is_active_{{ $timing->id }}" style="font-size: 0.78125rem; cursor: pointer;">
                                                {{ $timing->is_active ? 'Bật' : 'Tắt' }}
                                            </label>
                                        </div>
                                    </td>

                                    {{-- Sửa lẻ --}}
                                    <td class="text-center">
                                        <a href="{{ route('admin.order_status_timing.edit', $timing->id) }}"
                                           class="btn-action-icon edit" title="Chỉnh sửa chi tiết">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="form-actions-bar">
                    <button type="submit" class="btn-submit-modern">
                        <i class="fas fa-save"></i> Lưu toàn bộ cấu hình
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
@endsection
