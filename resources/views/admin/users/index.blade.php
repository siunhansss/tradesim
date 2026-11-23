@extends('layouts.app')

@section('title', 'Quản lý người dùng')

@section('content')
@include('admin._nav')

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto">Quản lý người dùng</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Thêm người dùng</a>
</div>

<form method="GET" class="row g-2 mb-3" role="search">
    <div class="col-sm-5 col-lg-4">
        <label for="q" class="visually-hidden">Tìm họ tên hoặc email</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Tìm họ tên hoặc email">
    </div>
    <div class="col-6 col-sm-3 col-lg-2">
        <label for="role" class="visually-hidden">Vai trò</label>
        <select id="role" name="role" class="form-select">
            <option value="">Mọi vai trò</option>
            @foreach (\App\Enums\Role::cases() as $r)
                <option value="{{ $r->value }}" @selected(($filters['role'] ?? '') === $r->value)>{{ $r->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-sm-3 col-lg-2">
        <label for="status" class="visually-hidden">Trạng thái</label>
        <select id="status" name="status" class="form-select">
            <option value="">Mọi trạng thái</option>
            @foreach (\App\Enums\UserStatus::cases() as $st)
                <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary">Lọc</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>@include('partials.sort-link', ['column' => 'name', 'label' => 'Họ tên'])</th>
                <th>@include('partials.sort-link', ['column' => 'email', 'label' => 'Email'])</th>
                <th>@include('partials.sort-link', ['column' => 'role', 'label' => 'Vai trò'])</th>
                <th>@include('partials.sort-link', ['column' => 'status', 'label' => 'Trạng thái'])</th>
                <th class="text-end">Số dư</th>
                <th class="text-end">Số lệnh</th>
                <th>@include('partials.sort-link', ['column' => 'created_at', 'label' => 'Ngày tạo'])</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($users as $u)
                <tr>
                    <td class="fw-semibold">{{ $u->name }} @if ($u->id === auth()->id())<span class="badge text-bg-info">Bạn</span>@endif</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->role->label() }}</td>
                    <td><span class="badge text-bg-{{ $u->isActive() ? 'success' : 'danger' }}">{{ $u->status->label() }}</span></td>
                    <td class="text-end num">{{ $u->wallet ? \App\Support\Format::money($u->wallet->balance) : '–' }}</td>
                    <td class="text-end num">@money($u->orders_count)</td>
                    <td class="num small">@datetime($u->created_at)</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        @if ($u->id !== auth()->id())
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delete-user-{{ $u->id }}">Xóa</button>
                            <div class="modal fade text-start" id="delete-user-{{ $u->id }}" tabindex="-1" aria-labelledby="delete-user-title-{{ $u->id }}" aria-hidden="true">
                                <div class="modal-dialog"><div class="modal-content">
                                    <div class="modal-header">
                                        <h2 class="modal-title fs-5" id="delete-user-title-{{ $u->id }}">Xóa tài khoản {{ $u->email }}?</h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                    </div>
                                    <div class="modal-body text-wrap">Chỉ xóa được tài khoản chưa có lệnh hoặc giao dịch. Tài khoản đã giao dịch hãy chuyển sang trạng thái “Bị khóa”.</div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                        <form method="POST" action="{{ route('admin.users.destroy', $u) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Xóa tài khoản</button>
                                        </form>
                                    </div>
                                </div></div>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-secondary py-5">Không có người dùng nào phù hợp với bộ lọc.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
