@extends('admin.layouts.master')
@section('title')
    Sửa cấu hình thời gian chuyển trạng thái
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Sửa cấu hình thời gian chuyển trạng thái</h1>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    {{ ucfirst($orderStatusTiming->from_status) }} → {{ ucfirst($orderStatusTiming->to_status) }}
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.order_status_timing.update', $orderStatusTiming->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label>Trạng thái bắt đầu</label>
                        <input type="text" class="form-control" value="{{ ucfirst($orderStatusTiming->from_status) }}" disabled>
                    </div>

                    <div class="form-group">
                        <label>Trạng thái đích</label>
                        <input type="text" class="form-control" value="{{ ucfirst($orderStatusTiming->to_status) }}" disabled>
                    </div>

                    <div class="form-group">
                        <label for="min_time">Thời gian tối thiểu <span class="text-danger">*</span></label>
                        <input type="number" 
                               class="form-control" 
                               id="min_time" 
                               name="min_time" 
                               value="{{ $orderStatusTiming->min_time }}" 
                               min="0" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="max_time">Thời gian tối đa <span class="text-danger">*</span></label>
                        <input type="number" 
                               class="form-control" 
                               id="max_time" 
                               name="max_time" 
                               value="{{ $orderStatusTiming->max_time }}" 
                               min="0" 
                               required>
                        <small class="form-text text-muted">Phải lớn hơn hoặc bằng thời gian tối thiểu</small>
                    </div>

                    <div class="form-group">
                        <label for="time_unit">Đơn vị thời gian <span class="text-danger">*</span></label>
                        <select class="form-control" id="time_unit" name="time_unit" required>
                            <option value="minutes" {{ $orderStatusTiming->time_unit == 'minutes' ? 'selected' : '' }}>Phút</option>
                            <option value="hours" {{ $orderStatusTiming->time_unit == 'hours' ? 'selected' : '' }}>Giờ</option>
                            <option value="days" {{ $orderStatusTiming->time_unit == 'days' ? 'selected' : '' }}>Ngày</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="3">{{ $orderStatusTiming->description }}</textarea>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   id="is_active" 
                                   name="is_active"
                                   {{ $orderStatusTiming->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Kích hoạt</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                        <a href="{{ route('admin.order_status_timing.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

