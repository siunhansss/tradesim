@extends('layouts.app')

@section('title', 'Sổ lệnh')

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto">Sổ lệnh</h1>
    @if ($openCount > 0)
        <a href="{{ route('orders.index', ['status' => 'open']) }}" class="badge text-bg-primary text-decoration-none">{{ $openCount }} lệnh đang mở</a>
    @endif
    <a href="{{ route('market.index') }}" class="btn btn-primary btn-sm">Đặt lệnh mới</a>
</div>

@include('orders._tabs')

<form method="GET" class="row g-2 mb-3" role="search">
    <div class="col-6 col-md-2">
        <label for="q" class="form-label small mb-1">Mã</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm text-uppercase" placeholder="VD: HPG">
    </div>
    <div class="col-6 col-md-2">
        <label for="status" class="form-label small mb-1">Trạng thái</label>
        <select id="status" name="status" class="form-select form-select-sm">
            <option value="">Tất cả</option>
            <option value="open" @selected(($filters['status'] ?? '') === 'open')>Đang mở</option>
            @foreach (\App\Enums\OrderStatus::cases() as $st)
                <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
            @endforeach
        </select>
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
        <label for="type" class="form-label small mb-1">Loại</label>
        <select id="type" name="type" class="form-select form-select-sm">
            <option value="">MP và LO</option>
            @foreach (\App\Enums\OrderType::cases() as $tp)
                <option value="{{ $tp->value }}" @selected(($filters['type'] ?? '') === $tp->value)>{{ $tp->value }}</option>
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
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary btn-sm">Lọc</button>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">Xóa lọc</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
            <tr>
                <th>@include('partials.sort-link', ['column' => 'created_at', 'label' => 'Thời gian'])</th>
                <th>Số hiệu</th>
                <th>Mã</th>
                <th>Chiều</th>
                <th>Loại</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'limit_price', 'label' => 'Giá đặt'])</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'quantity', 'label' => 'KL'])</th>
                <th class="text-end">Đã khớp</th>
                <th class="text-end">Giá khớp TB</th>
                <th>@include('partials.sort-link', ['column' => 'status', 'label' => 'Trạng thái'])</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td class="num small text-secondary">@datetime($order->created_at)</td>
                    <td class="num">#{{ $order->id }}</td>
                    <td><a href="{{ route('market.show', $order->stock->symbol) }}" class="fw-bold text-decoration-none num">{{ $order->stock->symbol }}</a></td>
                    <td class="fw-semibold {{ $order->side === \App\Enums\OrderSide::Buy ? 'text-success' : 'text-danger' }}">{{ $order->side->label() }}</td>
                    <td>{{ $order->type->value }}</td>
                    <td class="text-end num">{{ $order->limit_price ? \App\Support\Format::money($order->limit_price) : 'MP' }}</td>
                    <td class="text-end num">@money($order->quantity)</td>
                    <td class="text-end num">@money($order->filled_quantity)</td>
                    <td class="text-end num">{{ $order->filled_quantity ? \App\Support\Format::money($order->avg_fill_price) : '–' }}</td>
                    <td>
                        <span class="badge text-bg-{{ $order->status->badge() }}">{{ $order->status->label() }}</span>
                        @if ($order->reject_reason)<div class="small text-secondary text-wrap" style="max-width: 260px">{{ $order->reject_reason }}</div>@endif
                    </td>
                    <td class="text-end text-nowrap">
                        @can('cancel', $order)
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancel-{{ $order->id }}">
                                {{ $order->filled_quantity > 0 ? 'Hủy phần còn lại' : 'Hủy lệnh' }}
                            </button>
                            <div class="modal fade text-start" id="cancel-{{ $order->id }}" tabindex="-1" aria-labelledby="cancel-title-{{ $order->id }}" aria-hidden="true">
                                <div class="modal-dialog"><div class="modal-content">
                                    <div class="modal-header">
                                        <h2 class="modal-title fs-5" id="cancel-title-{{ $order->id }}">Hủy lệnh #{{ $order->id }}?</h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                    </div>
                                    <div class="modal-body text-wrap">
                                        {{ $order->side->label() }} {{ $order->stock->symbol }}: hủy @money($order->remainingQuantity()) cp chưa khớp.
                                        @if ($order->filled_quantity > 0) Phần đã khớp (@money($order->filled_quantity) cp) được giữ nguyên trong lịch sử. @endif
                                        {{ $order->side === \App\Enums\OrderSide::Buy ? 'Tiền đang giữ' : 'Cổ phiếu đang giữ' }} sẽ được giải phóng.
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Không hủy</button>
                                        <form method="POST" action="{{ route('orders.destroy', $order) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Hủy lệnh</button>
                                        </form>
                                    </div>
                                </div></div>
                            </div>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-secondary py-5">Không có lệnh nào phù hợp với bộ lọc.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $orders->links() }}</div>
@endsection
