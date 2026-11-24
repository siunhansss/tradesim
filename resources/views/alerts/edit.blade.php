@extends('layouts.app')

@section('title', 'Sửa cảnh báo')

@section('content')
<h1 class="h4 mb-3">Sửa cảnh báo {{ $alert->stock->symbol }}</h1>
<div class="card"><div class="card-body">
    <p class="small text-secondary">Giá hiện tại: <span class="num">@money($alert->stock->last_price)</span> đ</p>
    <form method="POST" action="{{ route('alerts.update', $alert) }}" class="row g-3" novalidate>
        @csrf
        @method('PUT')
        <div class="col-sm-4">
            <label for="condition" class="form-label">Điều kiện</label>
            <select id="condition" name="condition" class="form-select @error('condition') is-invalid @enderror">
                @foreach (\App\Enums\AlertCondition::cases() as $c)
                    <option value="{{ $c->value }}" @selected(old('condition', $alert->condition->value) === $c->value)>{{ $c->label() }}</option>
                @endforeach
            </select>
            @error('condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-sm-4">
            <label for="target_price" class="form-label">Ngưỡng giá (đ)</label>
            <input id="target_price" name="target_price" type="number" min="1" value="{{ old('target_price', $alert->target_price) }}" class="form-control num @error('target_price') is-invalid @enderror">
            @error('target_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-sm-4">
            <label for="status" class="form-label">Trạng thái</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                <option value="active" @selected(old('status', $alert->status->value) !== 'disabled')>Bật{{ $alert->status === \App\Enums\AlertStatus::Triggered ? ' lại' : '' }}</option>
                <option value="disabled" @selected(old('status', $alert->status->value) === 'disabled')>Tắt</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Lưu</button>
            <a href="{{ route('alerts.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div></div>
@endsection
