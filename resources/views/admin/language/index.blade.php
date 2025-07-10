@extends('admin.layouts.master')
@section('title')
Danh sách language
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_ngon_ngu";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary fs-5" id="tittle">Danh sách ngôn ngữ</h6>
            </div>
            <div id="div_btn_create" class="mb-2 d-flex justify-content-end">
                <a id="btn_create" href="{{route('language.create')}}" class="btn btn-success text-decoration-none btn-sm"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped rounded" id="dataTable" width="100%" cellspacing="0">
                    <thead class="position-sticky top-0">
                        <tr class="bg-primary">
                            <th class="tittle_column">#</th>
                            <th class="tittle_column">ID</th>
                            <th class="tittle_column">Tên ngôn ngữ</th>
                            <th class="tittle_column">Mã ngôn ngữ</th>
                            <th class="tittle_column">Hình ảnh</th>
                            <th class="tittle_column">Ngày tạo</th>
                            <th class="tittle_column">Ngày cập nhật</th>
                            <th class="tittle_column">Thao tác</th>
                        </tr>
                    </thead>
                    <tfoot class="sticky-bottom">
                        <tr>
                            <th class="tittle_column">#</th>
                            <th class="tittle_column">ID</th>
                            <th class="tittle_column">Tên ngôn ngữ</th>
                            <th class="tittle_column">Mã ngôn ngữ</th>
                            <th class="tittle_column">Hình ảnh</th>
                            <th class="tittle_column">Ngày tạo</th>
                            <th class="tittle_column">Ngày cập nhật</th>
                            <th class="tittle_column">Thao tác</th>
                        </tr>
                    </tfoot>
                    <tbody id="tbody">
                        @if (!empty($list_languages))
                        @foreach ($list_languages as $index =>$item)
                        <tr class="small">
                            <td>{{$index+1}}</td>
                            <td>{{$item->id}}</td>
                            <td>
                                <span><b><a class="cspt" href="{{ route('language.edit',['language'=>$item->id]) }}">{{Str::limit( $item->name ,30,'...')}}</a></b></span>
                            </td>
                            <td>
                                <span>{{$item->code}}</span>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center">
                                    <img class="index_image" src="{{ Storage::url($item->image) }}" alt="">
                                </div>
                            </td>
                            <td>{{$item->created_at}}</td>
                            <td>{{$item->updated_at}}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <div class="d-flex flex-row justify-content-center mt-1">
                                        <a href="{{ route('language.edit',['language'=>$item->id]) }}" class="btn btn-warning btn-sm d-flex align-items-center mr-1"><i class="fas fa-pen-to-square fa-sm p-2"></i></a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->
@endsection