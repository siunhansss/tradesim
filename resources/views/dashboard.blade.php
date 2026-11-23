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
    @php
        $s = $summary;
        // Màu theo dấu: lãi xanh, lỗ đỏ.
        $tone = fn ($v) => $v > 0 ? 'text-success' : ($v < 0 ? 'text-danger' : '');
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body">
            <div class="text-secondary small text-uppercase">Tổng tài sản</div>
            <div class="fs-4 fw-semibold num">@money($s['total_assets']) đ</div>
            <div class="small text-secondary">Vốn đầu tư <span class="num">@money($s['net_invested'])</span> đ</div>
        </div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body">
            <div class="text-secondary small text-uppercase">Lãi/lỗ và ROI</div>
            <div class="fs-4 fw-semibold num {{ $tone($s['profit']) }}">{{ \App\Support\Format::percent($s['roi']) }}</div>
            <div class="small num {{ $tone($s['profit']) }}">{{ \App\Support\Format::signed($s['profit']) }} đ</div>
        </div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body">
            <div class="text-secondary small text-uppercase">Tiền mặt</div>
            <div class="fs-4 fw-semibold num">@money($s['cash']) đ</div>
            <div class="small text-secondary">Khả dụng <span class="num">@money($s['available'])</span> · giữ <span class="num">@money($s['reserved'])</span></div>
        </div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body">
            <div class="text-secondary small text-uppercase">Giá trị cổ phiếu</div>
            <div class="fs-4 fw-semibold num">@money($s['stock_value']) đ</div>
            <div class="small">Chưa TH <span class="num {{ $tone($s['unrealized']) }}">{{ \App\Support\Format::signed($s['unrealized']) }}</span> · đã TH <span class="num {{ $tone($s['realized']) }}">{{ \App\Support\Format::signed($s['realized']) }}</span></div>
        </div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">Cổ phiếu nắm giữ</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Mã</th><th class="text-end">KL</th><th class="text-end">Giá vốn TB</th><th class="text-end">Giá</th><th class="text-end">Lãi/lỗ chưa TH</th><th class="text-end">Tỷ trọng</th></tr></thead>
                        <tbody>
                        @forelse (collect($s['rows'])->where('quantity', '>', 0) as $row)
                            <tr>
                                <td><a href="{{ route('market.show', $row['symbol']) }}" class="fw-semibold text-decoration-none">{{ $row['symbol'] }}</a></td>
                                <td class="text-end num">@money($row['quantity'])</td>
                                <td class="text-end num">@money($row['average_cost'])</td>
                                <td class="text-end num {{ $row['price_class'] }}">@money($row['price'])</td>
                                <td class="text-end num {{ $tone($row['unrealized']) }}">{{ \App\Support\Format::signed($row['unrealized']) }} ({{ \App\Support\Format::percent($row['unrealized_percent']) }})</td>
                                <td class="text-end num">{{ number_format($row['weight'], 1, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-secondary py-4">Chưa nắm giữ cổ phiếu nào. Vào <a href="{{ route('market.index') }}">bảng giá</a> để đặt lệnh đầu tiên.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white small text-secondary">
                    Giá vốn bình quân gồm phí mua. Lãi/lỗ chưa thực hiện tính theo giá mô phỏng gần nhất, chưa trừ phí và thuế bán (mục 6.2). Tiền mặt chiếm {{ number_format($s['cash_weight'], 1, ',', '.') }}% tổng tài sản.
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">Biến động tiền gần nhất <a href="{{ route('wallet.index') }}" class="small fw-normal">Xem sổ dòng tiền</a></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Thời gian</th><th>Loại</th><th class="text-end">Số tiền</th></tr></thead>
                        <tbody>
                        @forelse ($transactions as $tx)
                            <tr>
                                <td class="num small text-secondary">@datetime($tx->created_at)</td>
                                <td>{{ $tx->type->label() }}</td>
                                <td class="text-end num {{ $tx->type->affectsReservedOnly() ? 'text-secondary' : $tone($tx->amount) }}">{{ \App\Support\Format::signed($tx->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-secondary py-4">Chưa có biến động.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white small text-secondary">Dòng màu xám là tiền giữ hoặc giải phóng cho lệnh chờ, không làm đổi tổng số dư.</div>
            </div>
        </div>
    </div>
@endif
@endsection
