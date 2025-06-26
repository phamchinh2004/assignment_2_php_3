@extends('admin.layouts.master')
@section('title')
Chỉnh sửa quyền nhân viên
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/staff/edit_permission.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/staff/edit_permission.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('staff.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary fs-5" id="tittle">Chỉnh sửa quyền hạn của nhân viên <b><i class="text-danger">{{ $get_user->full_name }}</i></b> </h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <div class="row" id="list_permissions">
            @foreach($list_manager_settings as $item)
            <div class="permission_item d-flex justify-content-between align-items-center">
                <span>{{$item->manager_name}}</span>
                <div>
                    @php
                    $sub = $get_user_manager_setting->where('manager_setting_id', $item->id)->first();
                    @endphp
                    @if($sub)
                    @if($sub->is_active)
                    <i class="fa-solid fa-toggle-on fa-2xl cspt change_status_permission" data-id="{{ $sub->id }}"></i>
                    @else
                    <i class="fa-solid fa-toggle-off fa-2xl cspt change_status_permission" data-id="{{ $sub->id }}"></i>
                    @endif
                    @else
                    <i class="fa-solid fa-toggle-off fa-2xl cspt"></i>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

    </section>
</div>

@endsection