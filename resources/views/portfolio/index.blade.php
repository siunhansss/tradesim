@extends('layouts.app')

@section('title', 'Danh mục')

@section('content')
@php
    $s = $summary;
    $tone = fn ($v) => $v > 0 ? 'text-success' : ($v < 0 ? 'text-danger' : '');
    $cards = [
        ['Tổng tài sản', \App\Support\Format::money($s['total_assets']).' đ', ''],
        ['ROI', \App\Support\Format::percent($s['roi']), $tone($s['profit'])],
        ['Lãi/lỗ chưa thực hiện', \App\Support\Format::signed($s['unrealized']).' đ', $tone($s['unrealized'])],
        ['Lãi/lỗ đã thực hiện', \App\Support\Format::signed($s['realized']).' đ', $tone($s['realized'])],
        ['Giá trị cổ phiếu', \App\Support\Format::money($s['stock_value']).' đ', ''],
        ['Tiền mặt', \App\Support\Format::money($s['cash']).' đ', ''],
    ];
@endphp

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto">Danh mục đầu tư</h1>
    <a href="{{ route('market.index') }}" class="btn btn-outline-primary btn-sm">Đặt lệnh mới</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="row g-3">
            @foreach ($cards as [$label, $value, $class])
                <div class="col-sm-6 col-xl-4">
                    <div class="card h-100"><div class="card-body">
                        <div class="text-secondary small text-uppercase">{{ $label }}</div>
                        <div class="fs-5 fw-semibold num {{ $class }}">{{ $value }}</div>
                    </div></div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6">Phân bổ tài sản</h2>
                <div style="height: 220px"><canvas id="allocationChart" role="img" aria-label="Biểu đồ phân bổ tài sản theo mã và tiền mặt"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Đang nắm giữ</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>@include('partials.sort-link', ['column' => 'symbol', 'label' => 'Mã'])</th>
                <th class="text-end">KL</th>
                <th class="text-end">Khả dụng</th>
                <th class="text-end">Giá vốn TB</th>
                <th class="text-end">Giá hiện tại</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'value', 'label' => 'Giá trị'])</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'unrealized', 'label' => 'Lãi/lỗ chưa TH'])</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'unrealized_percent', 'label' => '%'])</th>
                <th class="text-end">Lãi/lỗ đã TH</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'weight', 'label' => 'Tỷ trọng'])</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($open as $row)
                <tr>
                    <td><a href="{{ route('market.show', $row['symbol']) }}" class="fw-bold text-decoration-none num">{{ $row['symbol'] }}</a><div class="small text-secondary">{{ $row['name'] }}</div></td>
                    <td class="text-end num">@money($row['quantity'])</td>
                    <td class="text-end num">@money($row['available'])@if ($row['available'] < $row['quantity'])<div class="small text-secondary">giữ @money($row['quantity'] - $row['available'])</div>@endif</td>
                    <td class="text-end num">@money($row['average_cost'])</td>
                    <td class="text-end num {{ $row['price_class'] }}">@money($row['price'])<div class="small text-secondary">@datetime($row['price_at'])</div></td>
                    <td class="text-end num">@money($row['value'])</td>
                    <td class="text-end num {{ $tone($row['unrealized']) }}">{{ \App\Support\Format::signed($row['unrealized']) }}</td>
                    <td class="text-end num {{ $tone($row['unrealized']) }}">{{ \App\Support\Format::percent($row['unrealized_percent']) }}</td>
                    <td class="text-end num {{ $tone($row['realized']) }}">{{ \App\Support\Format::signed($row['realized']) }}</td>
                    <td class="text-end num">{{ number_format($row['weight'], 1, ',', '.') }}%</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-secondary py-5">Chưa nắm giữ cổ phiếu nào. Vào <a href="{{ route('market.index') }}">bảng giá</a> để đặt lệnh đầu tiên.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-secondary">
        Giá vốn bình quân gia quyền, đã gồm phí mua. Lãi/lỗ chưa thực hiện tính theo giá mô phỏng gần nhất, chưa trừ phí và thuế khi bán (mục 6.2).
    </div>
</div>

@if ($closed->isNotEmpty())
    <div class="card">
        <div class="card-header bg-white fw-semibold">Vị thế đã bán hết</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light"><tr><th>Mã</th><th class="text-end">Lãi/lỗ đã thực hiện</th></tr></thead>
                <tbody>
                @foreach ($closed as $row)
                    <tr>
                        <td class="fw-bold num">{{ $row['symbol'] }} <span class="small text-secondary fw-normal">{{ $row['name'] }}</span></td>
                        <td class="text-end num {{ $tone($row['realized']) }}">{{ \App\Support\Format::signed($row['realized']) }} đ</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const data = @json($allocation);
        const palette = ['#1d5bc4', '#0b8a4c', '#8c2aa6', '#00829a', '#a07400', '#cf3328', '#5a6779', '#3f8efc', '#2fb36d', '#b65fd1'];
        new Chart(document.getElementById('allocationChart'), {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.label),
                datasets: [{
                    data: data.map(d => d.value),
                    backgroundColor: data.map((d, i) => d.label === 'Tiền mặt' ? '#d8dfe7' : palette[i % palette.length]),
                    borderWidth: 1,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    tooltip: { callbacks: { label: (c) => {
                        const total = c.dataset.data.reduce((a, b) => a + b, 0);
                        return c.label + ': ' + c.parsed.toLocaleString('vi-VN') + ' đ (' + (c.parsed / total * 100).toFixed(1).replace('.', ',') + '%)';
                    } } },
                },
            },
        });
    })();
</script>
@endpush
