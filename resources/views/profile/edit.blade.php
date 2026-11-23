@extends('layouts.app')

@section('title', 'Hồ sơ')

@section('content')
<h1 class="h4 mb-3">Hồ sơ cá nhân</h1>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Thông tin tài khoản</h2>
                <form method="POST" action="{{ route('profile.update') }}" novalidate>
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label for="name" class="form-label">Họ tên</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required autocomplete="name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <dl class="row small text-secondary mb-3">
                        <dt class="col-5 fw-normal">Vai trò</dt><dd class="col-7 mb-1">{{ $user->role->label() }}</dd>
                        <dt class="col-5 fw-normal">Trạng thái</dt><dd class="col-7 mb-1">{{ $user->status->label() }}</dd>
                        <dt class="col-5 fw-normal">Ngày tham gia</dt><dd class="col-7 mb-0 num">@datetime($user->created_at)</dd>
                    </dl>
                    <button type="submit" class="btn btn-primary">Lưu thông tin</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Đổi mật khẩu</h2>
                @if ($errors->password->any())
                    <div class="alert alert-danger py-2 small">Chưa đổi được mật khẩu. Kiểm tra các ô được đánh dấu.</div>
                @endif
                <form method="POST" action="{{ route('profile.password') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                        <input id="current_password" type="password" name="current_password" class="form-control @error('current_password', 'password') is-invalid @enderror" autocomplete="current-password">
                        @error('current_password', 'password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Mật khẩu mới</label>
                        <input id="new_password" type="password" name="password" class="form-control @error('password', 'password') is-invalid @enderror" autocomplete="new-password" aria-describedby="newPasswordHelp">
                        <div id="newPasswordHelp" class="form-text">Ít nhất 8 ký tự, có cả chữ và số.</div>
                        @error('password', 'password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Nhập lại mật khẩu mới</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Đổi mật khẩu</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
