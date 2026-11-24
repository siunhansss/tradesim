@extends('layouts.app')

@section('title', 'Theo dõi')

@section('content')
<h1 class="h4 mb-3">Theo dõi và cảnh báo</h1>
@include('alerts._tabs')

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr><th>Mã</th><th>Doanh nghiệp</th><th class="text-end">TC</th><th class="text-end">Giá</th><th class="text-end">+/− (%)</th><th class="text-end">Cảnh báo đang bật</th><th class="text-end">Thao tác</th></tr>
            </thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr>
                    <td><a href="{{ route('market.show', $stock->symbol) }}" class="fw-bold text-decoration-none num {{ $stock->priceClass() }}">{{ $stock->symbol }}</a></td>
                    <td class="small">{{ $stock->name }}</td>
                    <td class="text-end num px-ref">@money($stock->reference_price)</td>
                    <td class="text-end num fw-semibold {{ $stock->priceClass() }}">@money($stock->last_price)</td>
                    <td class="text-end num {{ $stock->priceClass() }}">{{ \App\Support\Format::percent($stock->changePercent()) }}</td>
                    <td class="text-end num">{{ $stock->active_alerts_count }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('alerts.index', ['stock_id' => $stock->id]) }}" class="btn btn-sm btn-outline-primary">Đặt cảnh báo</a>
                        <form method="POST" action="{{ route('watchlist.destroy', $stock) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Bỏ theo dõi</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary py-5">Chưa theo dõi mã nào. Mở một mã trên <a href="{{ route('market.index') }}">bảng giá</a> và bấm “Theo dõi”.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
