@extends('layouts.app')

@section('title', 'Cảnh báo giá')

@section('content')
<h1 class="h4 mb-3">Theo dõi và cảnh báo</h1>
@include('alerts._tabs')

<div class="card mb-3">
    <div class="card-body">
        <h2 class="h6">Tạo cảnh báo mới</h2>
        <form method="POST" action="{{ route('alerts.store') }}" class="row g-2 align-items-end" novalidate>
            @csrf
            <div class="col-sm-4 col-lg-3">
                <label for="stock_id" class="form-label small mb-1">Mã cổ phiếu</label>
                <select id="stock_id" name="stock_id" class="form-select @error('stock_id') is-invalid @enderror">
                    <option value="">Chọn mã</option>
                    @foreach ($stocks as $s)
                        <option value="{{ $s->id }}" @selected((int) old('stock_id', $selected) === $s->id)>{{ $s->symbol }} – giá @money($s->last_price)</option>
                    @endforeach
                </select>
                @error('stock_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-4 col-lg-3">
                <label for="condition" class="form-label small mb-1">Điều kiện</label>
                <select id="condition" name="condition" class="form-select @error('condition') is-invalid @enderror">
                    @foreach (\App\Enums\AlertCondition::cases() as $c)
                        <option value="{{ $c->value }}" @selected(old('condition') === $c->value)>{{ $c->label() }}</option>
                    @endforeach
                </select>
                @error('condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-4 col-lg-3">
                <label for="target_price" class="form-label small mb-1">Ngưỡng giá (đ)</label>
                <input id="target_price" name="target_price" type="number" min="1" value="{{ old('target_price') }}" class="form-control num @error('target_price') is-invalid @enderror">
                @error('target_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">Tạo cảnh báo</button></div>
        </form>
        <p class="small text-secondary mt-2 mb-0">Mỗi cảnh báo kích hoạt một lần rồi tự chuyển sang “Đã kích hoạt”; bật lại nếu muốn theo dõi tiếp. Tối đa {{ \App\Models\PriceAlert::MAX_ACTIVE_PER_USER }} cảnh báo đang bật.</p>
    </div>
</div>

<form method="GET" class="d-flex gap-2 mb-2">
    <label for="status" class="visually-hidden">Trạng thái</label>
    <select id="status" name="status" class="form-select form-select-sm w-auto">
        <option value="">Mọi trạng thái</option>
        @foreach (\App\Enums\AlertStatus::cases() as $st)
            <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
        @endforeach
    </select>
    <button class="btn btn-sm btn-outline-primary">Lọc</button>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Mã</th><th>Điều kiện</th><th class="text-end">Ngưỡng</th><th class="text-end">Giá hiện tại</th><th>Trạng thái</th><th>Kích hoạt lúc</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
            @forelse ($alerts as $alert)
                <tr>
                    <td class="fw-bold num">{{ $alert->stock->symbol }}</td>
                    <td>{{ $alert->condition->label() }}</td>
                    <td class="text-end num">@money($alert->target_price)</td>
                    <td class="text-end num">@money($alert->stock->last_price)</td>
                    <td><span class="badge text-bg-{{ ['active' => 'primary', 'triggered' => 'success', 'disabled' => 'secondary'][$alert->status->value] }}">{{ $alert->status->label() }}</span></td>
                    <td class="small num">{{ $alert->triggered_at ? \App\Support\Format::datetime($alert->triggered_at).' · giá '.\App\Support\Format::money($alert->triggered_price) : '–' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('alerts.edit', $alert) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form method="POST" action="{{ route('alerts.destroy', $alert) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary py-5">Chưa có cảnh báo nào.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $alerts->links() }}</div>
@endsection
