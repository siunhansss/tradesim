@extends('layouts.app')

@section('title', 'Báo cáo tháng '.$r['start']->format('m/Y'))

@section('content')
@php
    $tone = fn ($v) => $v > 0 ? 'text-success' : ($v < 0 ? 'text-danger' : '');
    $prev = $r['start']->copy()->subMonth()->format('Y-m');
    $next = $r['start']->copy()->addMonth();
    $nextAllowed = $next->lte(now(config('tradesim.display_timezone')));
@endphp
<h1 class="h4 mb-3">Quản lý tài chính danh mục</h1>
@include('finance._tabs')

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="{{ route('finance.report', $prev) }}" class="btn btn-sm btn-outline-secondary" aria-label="Tháng trước">‹</a>
    <h2 class="h5 mb-0">Tháng {{ $r['start']->format('m/Y') }}</h2>
    @if ($nextAllowed)
        <a href="{{ route('finance.report', $next->format('Y-m')) }}" class="btn btn-sm btn-outline-secondary" aria-label="Tháng sau">›</a>
    @endif
    <a href="{{ route('finance.report.csv', $r['month']) }}" class="btn btn-sm btn-primary ms-auto">Tải CSV</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h3 class="h6">Hiệu suất trong kỳ</h3>
            <dl class="row mb-0 num">
                <dt class="col-7 fw-normal text-secondary">Tổng tài sản đầu kỳ</dt><dd class="col-5 text-end">{{ $r['opening'] !== null ? \App\Support\Format::money($r['opening']) : '–' }}</dd>
                <dt class="col-7 fw-normal text-secondary">Tổng tài sản cuối kỳ</dt><dd class="col-5 text-end">{{ $r['closing'] !== null ? \App\Support\Format::money($r['closing']) : '–' }}</dd>
                <dt class="col-7 fw-normal text-secondary">ROI trong kỳ</dt><dd class="col-5 text-end {{ $tone($r['roi'] ?? 0) }}">{{ $r['roi'] !== null ? \App\Support\Format::percent($r['roi']) : '–' }}</dd>
                <dt class="col-7 fw-normal text-secondary">Sụt giảm tối đa</dt><dd class="col-5 text-end">{{ number_format($r['mdd'], 2, ',', '.') }}%</dd>
                <dt class="col-7 fw-normal text-secondary">Số ngày có ảnh chụp</dt><dd class="col-5 text-end mb-0">{{ $r['snapshot_days'] }}</dd>
            </dl>
            @if ($r['opening'] === null)
                <p class="small text-secondary mt-2 mb-0">Chưa có ảnh chụp cuối ngày trong hoặc trước tháng này nên chưa tính được ROI trong kỳ.</p>
            @endif
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h3 class="h6">Hoạt động giao dịch</h3>
            <dl class="row mb-0 num">
                <dt class="col-7 fw-normal text-secondary">Số lần khớp</dt><dd class="col-5 text-end">@money($r['trade_count'])</dd>
                <dt class="col-7 fw-normal text-secondary">Tổng giá trị mua</dt><dd class="col-5 text-end">@money($r['buy_value'])</dd>
                <dt class="col-7 fw-normal text-secondary">Tổng giá trị bán</dt><dd class="col-5 text-end">@money($r['sell_value'])</dd>
                <dt class="col-7 fw-normal text-secondary">Tổng phí + thuế</dt><dd class="col-5 text-end">@money($r['fees'] + $r['taxes'])</dd>
                <dt class="col-7 fw-normal text-secondary">Lãi/lỗ đã thực hiện</dt><dd class="col-5 text-end {{ $tone($r['realized']) }}">{{ \App\Support\Format::signed($r['realized']) }}</dd>
                <dt class="col-7 fw-normal text-secondary">Mã lãi nhiều nhất</dt><dd class="col-5 text-end">{{ $r['best'] ? $r['best']['symbol'].' '.\App\Support\Format::signed($r['best']['realized']) : '–' }}</dd>
                <dt class="col-7 fw-normal text-secondary">Mã lỗ nhiều nhất</dt><dd class="col-5 text-end mb-0">{{ $r['worst'] ? $r['worst']['symbol'].' '.\App\Support\Format::signed($r['worst']['realized']) : '–' }}</dd>
            </dl>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Giao dịch khớp trong tháng</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Thời gian</th><th>Mã</th><th>Chiều</th><th class="text-end">KL</th><th class="text-end">Giá</th><th class="text-end">Giá trị</th><th class="text-end">Phí</th><th class="text-end">Thuế</th><th class="text-end">Lãi/lỗ đã TH</th></tr></thead>
            <tbody>
            @forelse ($r['trades'] as $t)
                <tr>
                    <td class="num small">@datetime($t->executed_at)</td>
                    <td class="fw-semibold">{{ $t->stock->symbol }}</td>
                    <td>{{ $t->side->label() }}</td>
                    <td class="text-end num">@money($t->quantity)</td>
                    <td class="text-end num">@money($t->price)</td>
                    <td class="text-end num">@money($t->value)</td>
                    <td class="text-end num">@money($t->fee)</td>
                    <td class="text-end num">@money($t->tax)</td>
                    <td class="text-end num {{ $tone($t->realized_pnl ?? 0) }}">{{ $t->realized_pnl === null ? '–' : \App\Support\Format::signed($t->realized_pnl) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-secondary py-4">Không có giao dịch khớp trong tháng này.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-secondary">File CSV chứa đúng các số liệu trên trang này (tiêu chí mục 11).</div>
</div>
@endsection
