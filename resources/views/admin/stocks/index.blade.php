@extends('layouts.app')

@section('title', 'Quản lý mã cổ phiếu')

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto">Quản lý mã cổ phiếu</h1>
    <a href="{{ route('admin.stocks.create') }}" class="btn btn-primary">Thêm mã</a>
</div>

<form method="GET" class="row g-2 mb-3" role="search">
    <div class="col-sm-5 col-lg-4">
        <label for="q" class="visually-hidden">Tìm mã hoặc tên</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Tìm mã hoặc tên">
    </div>
    <div class="col-sm-4 col-lg-3">
        <label for="status" class="visually-hidden">Trạng thái</label>
        <select id="status" name="status" class="form-select">
            <option value="">Mọi trạng thái</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary">Lọc</button>
        <a href="{{ route('admin.stocks.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
    </div>
</form>

@php($sort = $filters['sort'] ?? 'symbol')
@php($dir = $filters['dir'] ?? 'asc')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>@include('partials.sort-link', ['column' => 'symbol', 'label' => 'Mã'])</th>
                <th>@include('partials.sort-link', ['column' => 'name', 'label' => 'Tên doanh nghiệp'])</th>
                <th>Sàn</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'reference_price', 'label' => 'Giá TC'])</th>
                <th>@include('partials.sort-link', ['column' => 'status', 'label' => 'Trạng thái'])</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr>
                    <td class="fw-bold num">{{ $stock->symbol }}</td>
                    <td>{{ $stock->name }}</td>
                    <td>{{ $stock->exchange }}</td>
                    <td class="text-end num">@money($stock->reference_price)</td>
                    <td><span class="badge text-bg-{{ $stock->status->badge() }}">{{ $stock->status->label() }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.stocks.edit', $stock) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $stock->id }}">Xóa</button>
                        <div class="modal fade text-start" id="delete-{{ $stock->id }}" tabindex="-1" aria-labelledby="delete-title-{{ $stock->id }}" aria-hidden="true">
                            <div class="modal-dialog"><div class="modal-content">
                                <div class="modal-header"><h2 class="modal-title fs-5" id="delete-title-{{ $stock->id }}">Xóa mã {{ $stock->symbol }}?</h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                                <div class="modal-body text-wrap">Chỉ xóa được mã chưa có lệnh hoặc giao dịch. Mã đã giao dịch cần chuyển sang “Ngừng hiển thị”.</div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                    <form method="POST" action="{{ route('admin.stocks.destroy', $stock) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Xóa mã</button>
                                    </form>
                                </div>
                            </div></div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-5">Không có mã nào phù hợp với bộ lọc.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $stocks->links() }}</div>
@endsection
