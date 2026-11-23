{{-- Form dùng chung cho thêm và sửa mã cổ phiếu. --}}
<div class="row g-3">
    <div class="col-sm-4">
        <label for="symbol" class="form-label">Mã cổ phiếu <span class="text-danger">*</span></label>
        <input id="symbol" name="symbol" maxlength="3" value="{{ old('symbol', $stock->symbol) }}" class="form-control num text-uppercase @error('symbol') is-invalid @enderror" required>
        @error('symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-8">
        <label for="name" class="form-label">Tên doanh nghiệp <span class="text-danger">*</span></label>
        <input id="name" name="name" value="{{ old('name', $stock->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-4">
        <label for="exchange" class="form-label">Sàn <span class="text-danger">*</span></label>
        <select id="exchange" name="exchange" class="form-select @error('exchange') is-invalid @enderror">
            @foreach (\App\Http\Requests\Admin\StockRequest::EXCHANGES as $exchange)
                <option value="{{ $exchange }}" @selected(old('exchange', $stock->exchange) === $exchange)>{{ $exchange }}</option>
            @endforeach
        </select>
        @error('exchange')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-4">
        <label for="industry" class="form-label">Ngành</label>
        <input id="industry" name="industry" value="{{ old('industry', $stock->industry) }}" class="form-control @error('industry') is-invalid @enderror">
        @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-4">
        <label for="reference_price" class="form-label">Giá tham chiếu (đ) <span class="text-danger">*</span></label>
        <input id="reference_price" type="number" name="reference_price" min="1000" step="10" value="{{ old('reference_price', $stock->reference_price) }}" class="form-control num @error('reference_price') is-invalid @enderror" required aria-describedby="refHelp">
        <div id="refHelp" class="form-text">Theo bước giá: 10 đ (&lt; 10.000), 50 đ (&lt; 50.000), 100 đ (từ 50.000).</div>
        @error('reference_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-4">
        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach (\App\Enums\StockStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $stock->status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label for="description" class="form-label">Mô tả</label>
        <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $stock->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
