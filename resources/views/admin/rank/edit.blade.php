@extends('admin.layouts.master')
@section('title')
Chỉnh sửa cấp độ
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/order/edit.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/order/edit.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('rank.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Sửa cấp độ <i class="text-dark">{{ $rank->name }}</i></h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <form action="{{ route('rank.update',['rank'=>$rank->id]) }}" method="post" enctype="multipart/form-data" id="form">
            @csrf
            @method('PUT')
            <div class="mt-2 fw-bold">
                <label for="">Tên cấp độ</label>
                <input type="text" name="name" value="{{ old('name',$rank->name) }}" class="form-control" placeholder="Nhập tên cấp độ (VD: Vip 1, vip 2 hoặc vip 3)">
                @error('name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="d-flex flex-row">
                <div class="mt-2 mr-3 w-50">
                    <label for="">Hình ảnh cũ</label>
                    <div class="div_image_old">
                        <img src="{{ asset('uploads/orders/images/'.$rank->image) }}" alt="">
                    </div>
                </div>
                <div class="mt-2 fw-bold w-50">
                    <label for="">Ảnh mới (có thể bỏ trống)</label>
                    <input type="file" accept="image/*" name="image" class="form-control">
                    @error('image')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <div class="mt-2 fw-bold">
                <label for="">Tỉ lệ hoa hồng</label>
                <input type="number" name="commission_percentage" value="{{ old('commission_percentage',$rank->commission_percentage) }}" class="form-control" placeholder="VD: 1 hoặc 0,001">
                @error('commission_percentage')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Phí nâng cấp</label>
                <input type="number" name="upgrade_fee" value="{{ old('upgrade_fee',$rank->upgrade_fee) }}" class="form-control" placeholder="VD: 100 hoặc 1000 (đơn vị $)">
                @error('upgrade_fee')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Số lượt quay</label>
                <input type="number" name="spin_count" value="{{ old('spin_count',$rank->spin_count) }}" class="form-control" placeholder="Nhập số lượt quay cho cấp độ này">
                @error('spin_count')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Tổng tiền giá trị cho tất cả đơn hàng của cấp độ này</label>
                <input type="number" name="value" value="{{ old('value',$rank->value) }}" class="form-control" placeholder="VD: 100 hoặc 1000 ($)">
                @error('value')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Số lượt rút tiền tối đa trong 1 ngày</label>
                <input type="number" name="maximum_number_of_withdrawals" value="{{ old('maximum_number_of_withdrawals',$rank->maximum_number_of_withdrawals) }}" class="form-control" placeholder="Nhập số lượt rút tiền tối đa trong 1 ngày">
                @error('maximum_number_of_withdrawals')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Số tiền được rút tối đa mỗi lượt</label>
                <input type="number" name="maximum_withdrawal_amount" value="{{ old('maximum_withdrawal_amount',$rank->maximum_withdrawal_amount) }}" class="form-control" placeholder="Nhập số tiền được rút tối đa mỗi lượt">
                @error('maximum_withdrawal_amount')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="d-flex mt-3 justify-content-center">
                <button class="btn btn-success" type="button" id="btn_submit">Xong</button>
            </div>
        </form>
    </section>
</div>

@endsection