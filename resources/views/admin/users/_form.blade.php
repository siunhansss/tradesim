{{-- Form dùng chung cho thêm và sửa người dùng. Biến: $user --}}
@php($editing = $user->exists)
<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Họ tên <span class="text-danger">*</span></label>
        <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="password" class="form-label">Mật khẩu @unless ($editing)<span class="text-danger">*</span>@endunless</label>
        <input id="password" type="password" name="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" aria-describedby="pwHelp">
        <div id="pwHelp" class="form-text">{{ $editing ? 'Để trống nếu giữ nguyên mật khẩu. ' : '' }}Ít nhất 8 ký tự, có cả chữ và số.</div>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Nhập lại mật khẩu</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="form-control">
    </div>
    <div class="col-md-6">
        <label for="role" class="form-label">Vai trò <span class="text-danger">*</span></label>
        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror">
            @foreach (\App\Enums\Role::cases() as $r)
                <option value="{{ $r->value }}" @selected(old('role', $user->role?->value) === $r->value)>{{ $r->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">Nhà đầu tư mới được cấp vốn ảo khởi tạo một lần.</div>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach (\App\Enums\UserStatus::cases() as $st)
                <option value="{{ $st->value }}" @selected(old('status', $user->status?->value) === $st->value)>{{ $st->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">Tài khoản bị khóa không đăng nhập và không đặt lệnh được.</div>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
