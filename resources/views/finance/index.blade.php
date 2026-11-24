@extends('layouts.app')

@section('title', 'Tài chính')

@section('content')
@php
    $s = $summary;
    $tone = fn ($v) => $v > 0 ? 'text-success' : ($v < 0 ? 'text-danger' : '');
@endphp
<h1 class="h4 mb-3">Quản lý tài chính danh mục</h1>
@include('finance._tabs')

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
        <div class="text-secondary small text-uppercase">Tổng tài sản</div>
        <div class="fs-5 fw-semibold num">@money($s['total_assets']) đ</div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
        <div class="text-secondary small text-uppercase">ROI từ đầu</div>
        <div class="fs-5 fw-semibold num {{ $tone($s['profit']) }}">{{ \App\Support\Format::percent($s['roi']) }}</div>
        <div class="small num {{ $tone($s['profit']) }}">{{ \App\Support\Format::signed($s['profit']) }} đ</div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
        <div class="text-secondary small text-uppercase">Đỉnh tổng tài sản</div>
        <div class="fs-5 fw-semibold num">{{ $peak ? \App\Support\Format::money($peak).' đ' : '–' }}</div>
        <div class="small text-secondary">theo ảnh chụp cuối ngày</div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
        <div class="text-secondary small text-uppercase">Sụt giảm tối đa (MDD)</div>
        <div class="fs-5 fw-semibold num {{ $mdd > 0 ? 'text-danger' : '' }}">{{ number_format($mdd, 2, ',', '.') }}%</div>
        <div class="small text-secondary">từ một đỉnh trước đó</div>
    </div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Tổng tài sản theo ngày</div>
    <div class="card-body">
        @if ($snapshots->count() < 2)
            <p class="text-secondary mb-0">Cần ít nhất 2 ảnh chụp cuối ngày để vẽ biểu đồ. Ảnh chụp được tạo lúc 15:05 mỗi ngày làm việc hoặc khi quản trị viên chốt phiên.
                Hiện có {{ $snapshots->count() }} ảnh chụp.</p>
        @else
            <div style="height: 300px"><canvas id="assetChart" role="img" aria-label="Biểu đồ tổng tài sản theo ngày"></canvas></div>
        @endif
    </div>
    <div class="card-footer bg-white small text-secondary">Ảnh chụp lưu tiền mặt, giá trị cổ phiếu và giá định giá lúc cuối ngày (quy trình 5.9c). Mỗi ngày tối đa một ảnh chụp.</div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Báo cáo theo tháng</div>
    <div class="list-group list-group-flush">
        @foreach ($months as $m)
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <span>Tháng {{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $m.'-01')->format('m/Y') }}</span>
                <span class="d-flex gap-2">
                    <a href="{{ route('finance.report', $m) }}" class="btn btn-sm btn-outline-primary">Xem</a>
                    <a href="{{ route('finance.report.csv', $m) }}" class="btn btn-sm btn-outline-secondary">Tải CSV</a>
                </span>
            </div>
        @endforeach
    </div>
</div>
@endsection

@if ($snapshots->count() >= 2)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('assetChart'), {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{ label: 'Tổng tài sản', data: @json($chartValues), borderColor: '#1d5bc4', backgroundColor: 'rgba(29,91,196,.12)', fill: true, tension: .2, pointRadius: 2 }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.parsed.y.toLocaleString('vi-VN') + ' đ' } } },
            scales: { y: { ticks: { callback: (v) => v.toLocaleString('vi-VN') } } },
        },
    });
</script>
@endpush
@endif
