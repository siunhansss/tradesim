@extends('layouts.app')

@section('title', 'Không tìm thấy trang')

@section('content')
<div class="text-center py-5">
    <div class="display-4 fw-bold text-danger num">404</div>
    <h1 class="h4">Không tìm thấy trang</h1>
    <p class="text-secondary">Đường dẫn không tồn tại hoặc dữ liệu đã bị ẩn.</p>
    <a href="{{ route('market.index') }}" class="btn btn-primary">Về bảng giá</a>
</div>
@endsection
