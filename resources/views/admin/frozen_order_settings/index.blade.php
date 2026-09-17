@extends('admin.layouts.master')

@section('title')
Cấu hình Frozen Order
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Cấu hình mốc cảnh báo Frozen Order</h6>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('frozen_order_settings.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Thời hạn xử lý tổng (giờ)</label>
                            <input type="number" name="processing_time_limit" class="form-control" min="1" value="{{ old('processing_time_limit', $settings->processing_time_limit ?? 24) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Cảnh báo lần 1 khi còn (giờ)</label>
                            <input type="number" name="notification_1_remaining_time" class="form-control" min="1" value="{{ old('notification_1_remaining_time', $settings->notification_1_remaining_time ?? 12) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Cảnh báo lần 2 khi còn (giờ)</label>
                            <input type="number" name="notification_2_remaining_time" class="form-control" min="1" value="{{ old('notification_2_remaining_time', $settings->notification_2_remaining_time ?? 1) }}" required>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mb-3">
                    <strong>Lưu ý:</strong> Các giá trị này là mặc định khi tạo Frozen Order mới. Mỗi order sẽ lưu snapshot riêng để không bị ảnh hưởng khi admin thay đổi cấu hình global sau đó.
                </div>

                <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
            </form>
        </div>
    </div>
</div>
@endsection
