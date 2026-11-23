@extends('layouts.app')

@section('title', 'Giao dịch khớp')

@section('content')
@php
    $tone = fn ($v) => $v > 0 ? 'text-success' : ($v < 0 ? 'text-danger' : '');
    $cards = [
        ['Số lần khớp', \App\Support\Format::money($totals->count), ''],
        ['Tổng giá trị', \App\Support\Format::money($totals->value).' đ', ''],
        ['Phí + thuế', \App\Support\Format::money($totals->fee + $totals->tax).' đ', ''],
        ['Lãi/lỗ đã thực hiện', \App\Support\Format::signed((int) $totals->realized).' đ', $tone((int) $totals->realized)],
    ];
@endphp
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto">Sổ lệnh</h1>
    <a href="{{ route('market.index') }}" class="btn btn-primary btn-sm">Đặt lệnh mới</a>
</div>

@include('orders._tabs')

<form method="GET" class="row g-2 mb-3" role="search">
    <div class="col-6 col-md-2">
        <label for="q" class="form-label small mb-1">Mã</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm text-uppercase" placeholder="VD: HPG">
    </div>
    <div class="col-6 col-md-2">
        <label for="side" class="form-label small mb-1">Chiều</label>
        <select id="side" name="side" class="form-select form-select-sm">
            <option value="">Mua và bán</option>
            @foreach (\App\Enums\OrderSide::cases() as $sd)
                <option value="{{ $sd->value }}" @selected(($filters['side'] ?? '') === $sd->value)>{{ $sd->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label for="from" class="form-label small mb-1">Từ ngày</label>
        <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
    </div>
    <div class="col-6 col-md-2">
        <label for="to" class="form-label small mb-1">Đến ngày</label>
        <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
    </div>
    <div class="col-12 col-md-4 d-flex align-items-end gap-2">
        <button class="btn btn-primary btn-sm">Lọc</button>
        <a href="{{ route('trades.index') }}" class="btn btn-outline-secondary btn-sm">Xóa lọc</a>
    </div>
</form>

<div class="row g-3 mb-3">
    @foreach ($cards as [$label, $value, $class])
        <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body py-2">
            <div class="text-secondary small text-uppercase">{{ $label }}</div>
            <div class="fw-semibold num {{ $class }}">{{ $value }}</div>
        </div></div></div>
    @endforeach
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>@include('partials.sort-link', ['column' => 'executed_at', 'label' => 'Thời gian'])</th>
                <th>Lệnh</th>
                <th>Mã</th>
                <th>Chiều</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'quantity', 'label' => 'KL'])</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'price', 'label' => 'Giá'])</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'value', 'label' => 'Giá trị'])</th>
                <th class="text-end">Phí</th>
                <th class="text-end">Thuế</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'realized_pnl', 'label' => 'Lãi/lỗ đã TH'])</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($trades as $trade)
                <tr>
                    <td class="num small text-secondary">@datetime($trade->executed_at)</td>
                    <td class="num">#{{ $trade->order_id }}</td>
                    <td class="fw-bold num">{{ $trade->stock->symbol }}</td>
                    <td class="fw-semibold {{ $trade->side === \App\Enums\OrderSide::Buy ? 'text-success' : 'text-danger' }}">{{ $trade->side->label() }}</td>
                    <td class="text-end num">@money($trade->quantity)</td>
                    <td class="text-end num">@money($trade->price)</td>
                    <td class="text-end num">@money($trade->value)</td>
                    <td class="text-end num">@money($trade->fee)</td>
                    <td class="text-end num">@money($trade->tax)</td>
                    <td class="text-end num {{ $tone($trade->realized_pnl ?? 0) }}">{{ $trade->realized_pnl === null ? '–' : \App\Support\Format::signed($trade->realized_pnl) }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-secondary py-5">Chưa có giao dịch khớp phù hợp với bộ lọc.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-secondary">Giao dịch đã khớp được lưu vĩnh viễn, không sửa, không xóa (BR-06). Phí và thuế lưu theo tỷ lệ tại thời điểm khớp (BR-13).</div>
</div>
<div class="mt-3">{{ $trades->links() }}</div>
@endsection
