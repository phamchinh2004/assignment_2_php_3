@extends('user.layouts.master')

@section('content')
    <div id="react-app-root"></div>
    <script id="react-page-bootstrap" type="application/json">@json($reactPageBootstrap)</script>
@endsection

@section('script-libs')
    @vite('resources/js/react/main.jsx')
@endsection
