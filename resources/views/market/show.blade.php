@extends('layouts.app')

@section('title', $stock->symbol)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('market.index') }}">Bảng giá</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $stock->symbol }}</li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <h1 class="h3 mb-0 num">{{ $stock->symbol }}
                            @if (! $stock->isTradable())<span class="badge text-bg-warning fs-6 align-middle">{{ $stock->status->label() }}</span>@endif
                        </h1>
                        <div class="text-secondary">{{ $stock->name }} · {{ $stock->exchange }}{{ $stock->industry ? ' · '.$stock->industry : '' }}</div>
                        @if ($canTrade)
                            <div class="d-flex gap-2 mt-2">
                                @if ($watching)
                                    <form method="POST" action="{{ route('watchlist.destroy', $stock) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-warning">★ Đang theo dõi</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('watchlist.store') }}">
                                        @csrf
                                        <input type="hidden" name="stock_id" value="{{ $stock->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">☆ Theo dõi</button>
                                    </form>
                                @endif
                                <a href="{{ route('alerts.index', ['stock_id' => $stock->id]) }}" class="btn btn-sm btn-outline-primary">Đặt cảnh báo giá</a>
                            </div>
                        @endif
                    </div>
                    <div class="text-end">
                        <div class="fs-3 fw-semibold num {{ $stock->priceClass() }}">@money($stock->last_price)</div>
                        <div class="num {{ $stock->priceClass() }}">{{ \App\Support\Format::signed($stock->change()) }} ({{ \App\Support\Format::percent($stock->changePercent()) }})</div>
                    </div>
                </div>
                <div class="mt-3" style="height: 300px">
                    <canvas id="priceChart" aria-label="Biểu đồ giá {{ $stock->symbol }}" role="img"></canvas>
                </div>
                <p class="small text-secondary mt-2 mb-0">
                    Nguồn: <strong>{{ $stock->price_source }}</strong> · Cập nhật: <span class="num">@datetime($stock->last_price_at)</span> · Đơn vị: đồng
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <dl class="row mb-0 num">
                    <dt class="col-6 fw-normal text-secondary">Trần</dt><dd class="col-6 text-end px-ceil">@money($stock->ceiling_price)</dd>
                    <dt class="col-6 fw-normal text-secondary">Tham chiếu</dt><dd class="col-6 text-end px-ref">@money($stock->reference_price)</dd>
                    <dt class="col-6 fw-normal text-secondary">Sàn</dt><dd class="col-6 text-end px-floor">@money($stock->floor_price)</dd>
                    <dt class="col-6 fw-normal text-secondary">Bước giá</dt><dd class="col-6 text-end">@money(\App\Models\Stock::tickSize($stock->last_price))</dd>
                    <dt class="col-6 fw-normal text-secondary">Tổng KL</dt><dd class="col-6 text-end mb-0">@money($stock->volume)</dd>
                </dl>
            </div>
        </div>
        @include('market._order-ticket')
        @if ($stock->description)
            <div class="card mt-3"><div class="card-body small">{{ $stock->description }}</div></div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const labels = @json($chartLabels);
        const prices = @json($chartPrices);
        const reference = {{ $stock->reference_price }};
        new Chart(document.getElementById('priceChart'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Giá', data: prices, borderColor: '#1d5bc4', backgroundColor: 'rgba(29,91,196,.12)', fill: true, tension: .2, pointRadius: 0 },
                    { label: 'Tham chiếu', data: prices.map(() => reference), borderColor: '#a07400', borderDash: [4, 4], pointRadius: 0, fill: false },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + c.parsed.y.toLocaleString('vi-VN') + ' đ' } } },
                scales: { y: { ticks: { callback: (v) => v.toLocaleString('vi-VN') } } },
            },
        });
    })();
</script>
@endpush
