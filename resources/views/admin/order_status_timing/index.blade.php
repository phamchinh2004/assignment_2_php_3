@extends('admin.layouts.master')
@section('title')
    Cấu hình thời gian chuyển trạng thái đơn hàng
@endsection

@section('style-libs')
    @vite('resources/css/admin/order_status_timing/index.css')
@endsection

@section('script-libs')
    @vite('resources/js/admin/order_status_timing/index.js')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Cấu hình thời gian chuyển trạng thái đơn hàng</h1>
        </div>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Danh sách cấu hình</h6>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if($timings->isEmpty())
                    <div class="alert alert-warning">
                        <h5 class="alert-heading">Chưa có dữ liệu cấu hình!</h5>
                        <p>Vui lòng chạy migration và seeder để tạo dữ liệu mặc định:</p>
                        <hr>
                        <pre class="mb-0">php artisan migrate
php artisan db:seed --class=OrderStatusTimingSeeder</pre>
                        <p class="mb-0 mt-2">Hoặc chạy seeder tự động:</p>
                        <pre class="mb-0">php artisan db:seed</pre>
                    </div>
                @else
                <form id="timingForm" method="POST" action="{{ route('admin.order_status_timing.update_multiple') }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Trạng thái bắt đầu</th>
                                    <th>Trạng thái đích</th>
                                    <th>Thời gian tối thiểu</th>
                                    <th>Thời gian tối đa</th>
                                    <th>Đơn vị</th>
                                    <th>Mô tả</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timings as $timing)
                                <tr>
                                    <td>
                                        <span class="badge badge-info">{{ ucfirst($timing->from_status) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">{{ ucfirst($timing->to_status) }}</span>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               name="timings[{{ $timing->id }}][min_time]" 
                                               value="{{ $timing->min_time }}" 
                                               class="form-control form-control-sm" 
                                               min="0" 
                                               required>
                                        <input type="hidden" name="timings[{{ $timing->id }}][id]" value="{{ $timing->id }}">
                                    </td>
                                    <td>
                                        <input type="number" 
                                               name="timings[{{ $timing->id }}][max_time]" 
                                               value="{{ $timing->max_time }}" 
                                               class="form-control form-control-sm" 
                                               min="0" 
                                               required>
                                    </td>
                                    <td>
                                        <select name="timings[{{ $timing->id }}][time_unit]" class="form-control form-control-sm" required>
                                            <option value="minutes" {{ $timing->time_unit == 'minutes' ? 'selected' : '' }}>Phút</option>
                                            <option value="hours" {{ $timing->time_unit == 'hours' ? 'selected' : '' }}>Giờ</option>
                                            <option value="days" {{ $timing->time_unit == 'days' ? 'selected' : '' }}>Ngày</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="timings[{{ $timing->id }}][description]" 
                                               value="{{ $timing->description }}" 
                                               class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="is_active_{{ $timing->id }}" 
                                                   name="timings[{{ $timing->id }}][is_active]"
                                                   {{ $timing->is_active ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="is_active_{{ $timing->id }}">
                                                {{ $timing->is_active ? 'Kích hoạt' : 'Vô hiệu hóa' }}
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.order_status_timing.edit', $timing->id) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Sửa
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Lưu tất cả
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
@endsection

