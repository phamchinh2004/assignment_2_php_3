@extends('admin.layouts.master')

@section('title', 'Chat System')
@push('css')
@vite('resources/css/admin/chat/chat-component.css')
@endpush
@section('content')
<div class="h-screen">
    @livewire('admin.chat-component')
</div>
@endsection

@push('scripts')
<script>
    
</script>
@endpush