@extends('layouts.app')

@section('title', 'Tổng quan')

@section('content')
<h1 class="h4 mb-3">Xin chào, {{ $user->name }}</h1>

@if ($user->isAdmin())
    <div class="row g-3 mb-4">
        @foreach ($stats as $label => $value)
            <div class="col-sm-4">
                <div class="card"><div class="card-body">
                    <div class="text-secondary small text-uppercase">{{ $label }}</div>
                    <div class="fs-4 fw-semibold num">@money($value)</div>
                </div></div>
            </div>
        @endforeach
    </div>
    <a href="{{ route('admin.stocks.index') }}" class="btn btn-primary">Quản lý mã cổ phiếu</a>
@else
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body">
            <div class="text-secondary small text-uppercase">Tổng số dư</div>
            <div class="fs-4 fw-semibold num">@money($wallet?->balance) đ</div>
        </div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body">
            <div class="text-secondary small text-uppercase">Đang giữ cho lệnh chờ</div>
            <div class="fs-4 fw-semibold num">@money($wallet?->reserved) đ</div>
        </div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body">
            <div class="text-secondary small text-uppercase">Số dư khả dụng</div>
            <div class="fs-4 fw-semibold num text-success">@money($wallet?->available()) đ</div>
        </div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">Cổ phiếu nắm giữ</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Mã</th><th class="text-end">Khối lượng</th><th class="text-end">Giá vốn TB</th></tr></thead>
                        <tbody>
                        @forelse ($holdings as $holding)
                            <tr>
                                <td class="fw-semibold">{{ $holding->stock->symbol }}</td>
                                <td class="text-end num">@money($holding->quantity)</td>
                                <td class="text-end num">@money($holding->averageCost())</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-secondary py-4">Chưa nắm giữ cổ phiếu nào. Chức năng đặt lệnh có từ Sprint 2.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">Biến động tiền gần nhất</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Thời gian</th><th>Loại</th><th class="text-end">Số tiền</th></tr></thead>
                        <tbody>
                        @forelse ($transactions as $tx)
                            <tr>
                                <td class="num small text-secondary">@datetime($tx->created_at)</td>
                                <td>{{ $tx->type->label() }}</td>
                                <td class="text-end num {{ $tx->amount >= 0 ? 'text-success' : 'text-danger' }}">{{ \App\Support\Format::signed($tx->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-secondary py-4">Chưa có biến động.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
