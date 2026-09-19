@extends('admin.layouts.master')
@section('title')
    Thêm mới đơn hàng
@endsection

@section('style-libs')
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    @vite(['resources/css/admin/common-modern.css', 'resources/css/admin/order/create.css'])
@endsection

@section('script-libs')
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    @vite('resources/js/admin/order/create.js')
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
    <script>
        window.currentPermissionCode = "quan_ly_don_hang";
    </script>
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">
    {{-- Back button --}}
    <a href="{{ route('order.index') }}" class="btn-back-modern mb-3 d-inline-flex">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách đơn hàng
    </a>

    {{-- Main create card --}}
    <div class="card create-order-card shadow-sm border-0 rounded-lg">
        <div class="card-header create-card-header py-3">
            <div class="d-flex align-items-center">
                <span class="create-header-icon mr-2"><i class="fas fa-magic"></i></span>
                <div>
                    <h5 class="m-0 font-weight-bold text-dark" id="tittle" style="font-size:16px;">Tạo tự động đơn hàng</h5>
                    <small class="text-muted">Chọn cấp độ và tải ảnh để hệ thống tự động sinh các đơn hàng</small>
                </div>
            </div>
        </div>

        <div class="card-body p-4 section_1_content">
            <div class="row">
                <div class="col-12 col-md-8 mx-auto">
                    {{-- Select rank --}}
                    <div class="form-group mb-4">
                        <label for="rank" class="create-form-label">
                            <i class="fas fa-crown text-warning mr-1"></i> Chọn cấp độ (Rank) <span class="text-danger">*</span>
                        </label>
                        <select name="" id="rank" class="form-control create-select">
                            <option value="">--- Chọn cấp độ ---</option>
                            @if (!empty($list_ranks))
                                @foreach ($list_ranks as $rank)
                                    <option value="{{ $rank['id'] }}"
                                        data-quantity="{{ $rank['quantity'] }}"
                                        data-value="{{ $rank['value'] }}"
                                        data-start="{{ $rank['start'] }}"
                                        data-spin_count="{{ $rank['spin_count'] }}"
                                        data-commission_percentage="{{ $rank['commission_percentage'] }}"
                                        {{ $rank['quantity'] == 0 ? "disabled" : "" }}>
                                        {{ $rank['name'] }} — Cần {{ $rank['quantity'] }} hình ảnh
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- Upload images --}}
                    <div class="form-group mb-4">
                        <label for="images" class="create-form-label">
                            <i class="fas fa-images text-primary mr-1"></i> Hình ảnh đơn hàng <span class="text-danger">*</span>
                        </label>
                        <div class="upload-dropzone p-4 text-center">
                            <i class="fas fa-cloud-upload-alt text-muted mb-2" style="font-size:36px;"></i>
                            <p class="mb-1 font-weight-medium text-dark" style="font-size:14px;">Chọn nhiều ảnh (Mỗi ảnh tương ứng 1 đơn hàng)</p>
                            <p class="text-danger font-weight-bold mb-2" style="font-size:12px;">
                                <i class="fas fa-info-circle mr-1"></i>Tối đa 20 đơn hàng mỗi lần tạo!
                            </p>
                            <input id="images" accept="image/*" type="file" class="form-control-file d-inline-block" multiple style="max-width:320px;">
                        </div>
                    </div>

                    {{-- Submit action --}}
                    <div class="d-flex justify-content-center mt-4">
                        <button class="btn btn-primary px-4 py-2 font-weight-bold" id="btn_generate_auto" style="border-radius:8px;">
                            <i class="fas fa-bolt mr-1"></i> Bắt đầu tạo tự động
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="order_items container-fluid mt-3">
        <div class="row d-flex justify-content-between align-items-center" id="list_orders"></div>
    </section>

    <div id="submit" hidden>
        <div class="d-flex mt-3 justify-content-center">
            <button class="btn btn-secondary csdf" id="btn_submit">Xong</button>
        </div>
    </div>
</div>
@endsection