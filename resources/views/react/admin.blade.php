@extends('admin.layouts.master')

@section('title')
    {{ $title ?? config('app.name') }}
@endsection

@section('script-libs')
    @vite('resources/js/react/main.jsx')
@endsection

@section('content')
    <div id="react-app-root"></div>
    <script id="react-page-bootstrap" type="application/json">{!! json_encode($reactPageBootstrap ?? ['page' => null, 'props' => []], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection
