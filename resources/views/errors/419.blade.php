@extends('layouts.app')

@section('title', 'Phiên làm việc đã hết hạn')

@section('content')
<div class="text-center py-5">
    <div class="display-4 fw-bold text-danger num">419</div>
    <h1 class="h4">Phiên làm việc đã hết hạn</h1>
    <p class="text-secondary">Biểu mẫu đã quá hạn để bảo vệ chống giả mạo yêu cầu (CSRF). Hãy tải lại trang rồi gửi lại.</p>
    <a href="{{ route('market.index') }}" class="btn btn-primary">Về bảng giá</a>
</div>
@endsection
