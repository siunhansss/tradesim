@extends('layouts.app')

@section('title', 'Ví ảo')

@section('content')
@php
    $tone = fn ($v) => $v > 0 ? 'text-success' : ($v < 0 ? 'text-danger' : '');
@endphp
<h1 class="h4 mb-3">Ví ảo và sổ dòng tiền</h1>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <div class="text-secondary small text-uppercase">Tổng số dư hiện tại</div>
        <div class="fs-5 fw-semibold num">@money($wallet->balance) đ</div>
    </div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <div class="text-secondary small text-uppercase">Đang giữ cho lệnh chờ</div>
        <div class="fs-5 fw-semibold num">@money($wallet->reserved) đ</div>
    </div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <div class="text-secondary small text-uppercase">Số dư khả dụng</div>
        <div class="fs-5 fw-semibold num text-success">@money($wallet->available()) đ</div>
    </div></div></div>
</div>

<form method="GET" class="row g-2 mb-3" role="search">
    <div class="col-6 col-md-3">
        <label for="from" class="form-label small mb-1">Từ ngày</label>
        <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
    </div>
    <div class="col-6 col-md-3">
        <label for="to" class="form-label small mb-1">Đến ngày</label>
        <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
    </div>
    <div class="col-6 col-md-3">
        <label for="type" class="form-label small mb-1">Loại biến động</label>
        <select id="type" name="type" class="form-select form-select-sm">
            <option value="">Tất cả</option>
            @foreach (\App\Enums\LedgerType::cases() as $lt)
                <option value="{{ $lt->value }}" @selected(($filters['type'] ?? '') === $lt->value)>{{ $lt->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-3 d-flex align-items-end gap-2">
        <button class="btn btn-primary btn-sm">Xem</button>
        <a href="{{ route('wallet.index') }}" class="btn btn-outline-secondary btn-sm">Tháng này</a>
    </div>
</form>

<div class="card mb-3">
    <div class="card-body">
        <div class="row text-center g-2 num">
            <div class="col-6 col-md-3"><div class="small text-secondary">Số dư đầu kỳ</div><div class="fw-semibold">@money($opening)</div></div>
            <div class="col-6 col-md-3"><div class="small text-secondary">Tiền vào</div><div class="fw-semibold text-success">{{ \App\Support\Format::signed($inflow) }}</div></div>
            <div class="col-6 col-md-3"><div class="small text-secondary">Tiền ra</div><div class="fw-semibold text-danger">{{ \App\Support\Format::signed($outflow) }}</div></div>
            <div class="col-6 col-md-3"><div class="small text-secondary">Số dư cuối kỳ</div><div class="fw-semibold">@money($closing)</div></div>
        </div>
        <p class="small mb-0 mt-2 {{ $balanced ? 'text-success' : 'text-danger' }}">
            {{ $balanced ? '✓ Khớp' : '✗ Lệch' }}: đầu kỳ + tiền vào + tiền ra {{ $balanced ? '=' : '≠' }} cuối kỳ
            (kỳ {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d/m/Y') }}).
            Bút toán giữ và giải phóng tiền không làm đổi tổng số dư nên không tính vào tiền vào, tiền ra.
        </p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>Thời gian</th>
                <th>Loại</th>
                <th class="text-end">Số tiền</th>
                <th class="text-end">Số dư sau</th>
                <th class="text-end">Tiền giữ sau</th>
                <th>Tham chiếu</th>
                <th>Diễn giải</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($transactions as $tx)
                @php($reservedOnly = $tx->type->affectsReservedOnly())
                <tr @class(['text-secondary' => $reservedOnly])>
                    <td class="num small">@datetime($tx->created_at)</td>
                    <td><span class="badge {{ $reservedOnly ? 'text-bg-light border' : 'text-bg-secondary' }}">{{ $tx->type->label() }}</span></td>
                    <td class="text-end num {{ $reservedOnly ? '' : $tone($tx->amount) }}">{{ \App\Support\Format::signed($tx->amount) }}</td>
                    <td class="text-end num">@money($tx->balance_after)</td>
                    <td class="text-end num">@money($tx->reserved_after)</td>
                    <td class="num">
                        @if ($tx->reference_type === $orderMorph)
                            <a href="{{ route('orders.index') }}" class="text-decoration-none">Lệnh #{{ $tx->reference_id }}</a>
                        @else
                            –
                        @endif
                    </td>
                    <td class="small">{{ $tx->note }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary py-5">Không có biến động nào trong kỳ đã chọn.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-secondary">Sổ chỉ ghi thêm, không sửa, không xóa. Mọi số dư hiển thị tính lại được từ sổ này (BR-14).</div>
</div>
<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
