@extends('admin.layouts.master')
@section('title')
Chỉnh sửa người dùng
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
    <a href="{{route('user.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Sửa người dùng <i class="text-dark">{{ $user->name }}</i></h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <form action="{{ route('user.update',['user'=>$user->id]) }}" method="post" enctype="multipart/form-data" id="form">
            @csrf
            @method('PUT')
            <div class="mt-2 fw-bold">
                <label for="">Họ và tên</label>
                <input type="text" name="full_name" id="full_name" value="{{ old('full_name',$user->full_name) }}" class="form-control" placeholder="Nhập họ và tên thật">
                @error('full_name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Tên đăng nhập</label>
                <input type="text" name="username" id="username" value="{{ old('username',$user->username) }}" class="form-control" placeholder="Nhập tên đăng nhập">
                @error('username')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Số điện thoại</label>
                <input type="number" name="phone" id="phone" value="{{ old('phone',$user->phone) }}" class="form-control" placeholder="Nhập số điện thoại">
                @error('phone')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Chọn cấp độ</label>
                <select name="rank" id="" class="form-select">
                    <option value="">--- Chọn cấp độ ---</option>
                    @if (!empty($list_ranks))
                    @foreach ($list_ranks as $rank)
                    <option value="{{ $rank['id'] }}"
                        {{ $rank['id']==$user->rank_id?"selected":"" }}>
                        {{ $rank['name']}} - Có
                        {{ $rank['spin_count']}} đơn hàng
                    </option>
                    @endforeach
                    @endif
                </select>
                @error('rank')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold form-check">
                <input type="checkbox" name="reset_progress" id="reset_progress" class="form-check-input" style="transform: scale(1.5);">
                <label class="form-check-label nsl" for="reset_progress">Làm mới tiến trình</label>
            </div>
            <div class="d-flex mt-3 justify-content-center">
                <button class="btn btn-warning" type="button" id="btn_submit">Xong</button>
            </div>
        </form>
    </section>
</div>

@endsection