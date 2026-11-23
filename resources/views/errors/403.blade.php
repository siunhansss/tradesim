@extends('layouts.app')

@section('title', 'Bạn không có quyền truy cập')

@section('content')
<div class="text-center py-5">
    <div class="display-4 fw-bold text-danger num">403</div>
    <h1 class="h4">Bạn không có quyền truy cập</h1>
    <p class="text-secondary">Trang này chỉ dành cho quản trị viên hoặc cho chủ sở hữu dữ liệu.</p>
    <a href="{{ route('market.index') }}" class="btn btn-primary">Về bảng giá</a>
</div>
@endsection
