@extends('layouts.app')

@section('title', 'Thêm người dùng')

@section('content')
@include('admin._nav')
<h1 class="h4 mb-3">Thêm người dùng</h1>
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
        @csrf
        @include('admin.users._form')
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Tạo tài khoản</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div></div>
@endsection
