@extends('layouts.app')

@section('title', 'Đăng nhập')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Đăng nhập</h1>
                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-3">
                        <input id="remember" type="checkbox" name="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">Ghi nhớ đăng nhập</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                </form>
                <p class="text-secondary small mt-3 mb-0">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký và nhận vốn ảo</a>.</p>
            </div>
        </div>
        @if (app()->environment('local'))
            <div class="card mt-3">
                <div class="card-body small">
                    <strong>Tài khoản demo (chỉ môi trường local)</strong><br>
                    Admin: <span class="num">admin@tradesim.test / admin12345</span><br>
                    Trader: <span class="num">minhanh@tradesim.test / trader12345</span>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
