@extends('layouts.app')

@section('title', 'Bảng giá')

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto">Bảng giá mô phỏng</h1>
    <span class="badge {{ $marketOpen ? 'text-bg-success' : 'text-bg-danger' }}">{{ $marketOpen ? 'Phiên đang mở' : 'Phiên đã đóng' }}</span>
    <span class="small text-secondary">
        Nguồn: giá mô phỏng · Cập nhật lần cuối: <span class="num">@datetime($lastUpdate)</span>
    </span>
</div>

@if (! $lastUpdate || $lastUpdate->lt(now()->subMinutes(5)))
    <div class="alert alert-warning py-2">Giá chưa được cập nhật trong 5 phút gần đây. Đây không phải giá thời gian thực.</div>
@endif

<form method="GET" class="row g-2 mb-3" role="search">
    <div class="col-sm-5 col-lg-4">
        <label for="q" class="visually-hidden">Tìm mã hoặc tên</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Tìm mã hoặc tên doanh nghiệp">
    </div>
    <div class="col-sm-4 col-lg-3">
        <label for="industry" class="visually-hidden">Ngành</label>
        <select id="industry" name="industry" class="form-select">
            <option value="">Tất cả ngành</option>
            @foreach ($industries as $industry)
                <option value="{{ $industry }}" @selected(($filters['industry'] ?? '') === $industry)>{{ $industry }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary">Lọc</button>
        <a href="{{ route('market.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>@include('partials.sort-link', ['column' => 'symbol', 'label' => 'Mã'])</th>
                <th>Doanh nghiệp</th>
                <th class="text-end">Trần</th>
                <th class="text-end">TC</th>
                <th class="text-end">Sàn</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'last_price', 'label' => 'Giá'])</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'change', 'label' => '+/− (%)'])</th>
                <th class="text-end">@include('partials.sort-link', ['column' => 'volume', 'label' => 'Tổng KL'])</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr>
                    <td>
                        <a href="{{ route('market.show', $stock->symbol) }}" class="fw-bold text-decoration-none num {{ $stock->priceClass() }}">{{ $stock->symbol }}</a>
                        @if (! $stock->isTradable())<span class="badge text-bg-warning">{{ $stock->status->label() }}</span>@endif
                    </td>
                    <td class="small">{{ $stock->name }}</td>
                    <td class="text-end num px-ceil">@money($stock->ceiling_price)</td>
                    <td class="text-end num px-ref">@money($stock->reference_price)</td>
                    <td class="text-end num px-floor">@money($stock->floor_price)</td>
                    <td class="text-end num fw-semibold {{ $stock->priceClass() }}">@money($stock->last_price)</td>
                    <td class="text-end num {{ $stock->priceClass() }}">{{ \App\Support\Format::signed($stock->change()) }} ({{ \App\Support\Format::percent($stock->changePercent()) }})</td>
                    <td class="text-end num">@money($stock->volume)</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-secondary py-5">Không tìm thấy mã nào phù hợp. Thử từ khóa khác hoặc xóa bộ lọc.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $stocks->links() }}</div>
@endsection
