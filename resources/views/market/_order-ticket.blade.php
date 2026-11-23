{{-- Phiếu đặt lệnh (FR-06, FR-07). Biến: $stock, $canTrade, $wallet, $holding, $marketOpen, $feeRate, $taxRate --}}
<div class="card">
    <div class="card-body">
        <h2 class="h6 mb-3">Đặt lệnh</h2>

        @guest
            <p class="small text-secondary">Đăng nhập để đặt lệnh mô phỏng. Tài khoản mới được cấp vốn ảo một lần.</p>
            <a href="{{ route('login') }}" class="btn btn-primary w-100">Đăng nhập</a>
        @else
            @if (! $canTrade)
                <p class="small text-secondary mb-0">Tài khoản quản trị hoặc tài khoản đang bị khóa không được đặt lệnh.</p>
            @else
                @if (! $marketOpen)
                    <div class="alert alert-warning py-2 small">Phiên mô phỏng đang đóng. Lệnh mới sẽ bị từ chối.</div>
                @elseif (! $stock->isTradable())
                    <div class="alert alert-warning py-2 small">Mã {{ $stock->symbol }} đang ngừng giao dịch.</div>
                @endif

                @php($side = old('side', 'buy'))
                @php($type = old('type', 'LO'))
                <form method="POST" action="{{ route('orders.store') }}" id="order-ticket" novalidate>
                    @csrf
                    <input type="hidden" name="stock_id" value="{{ $stock->id }}">
                    {{-- Mã yêu cầu mới cho mỗi lần mở phiếu; gửi lại cùng mã không tạo lệnh trùng (BR-08). --}}
                    <input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

                    <div class="btn-group w-100 mb-3" role="group" aria-label="Chiều lệnh">
                        <input type="radio" class="btn-check" name="side" id="side-buy" value="buy" @checked($side === 'buy')>
                        <label class="btn btn-outline-success" for="side-buy">Mua</label>
                        <input type="radio" class="btn-check" name="side" id="side-sell" value="sell" @checked($side === 'sell')>
                        <label class="btn btn-outline-danger" for="side-sell">Bán</label>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label small">Loại lệnh</label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                            @foreach (\App\Enums\OrderType::cases() as $orderType)
                                <option value="{{ $orderType->value }}" @selected($type === $orderType->value)>{{ $orderType->label() }}</option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6" id="limit-price-group">
                            <label for="limit_price" class="form-label small">Giá đặt (đ)</label>
                            <input id="limit_price" name="limit_price" type="number" inputmode="numeric"
                                   min="{{ $stock->floor_price }}" max="{{ $stock->ceiling_price }}" step="{{ \App\Models\Stock::tickSize($stock->last_price) }}"
                                   value="{{ old('limit_price', $stock->last_price) }}"
                                   class="form-control num @error('limit_price') is-invalid @enderror">
                            @error('limit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label for="quantity" class="form-label small">Khối lượng (cp)</label>
                            <input id="quantity" name="quantity" type="number" inputmode="numeric" min="100" step="100"
                                   value="{{ old('quantity', 100) }}"
                                   class="form-control num @error('quantity') is-invalid @enderror">
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <dl class="row small mb-3 num">
                        <dt class="col-7 fw-normal text-secondary">Tiền khả dụng</dt>
                        <dd class="col-5 text-end mb-1">@money($wallet?->available()) đ</dd>
                        <dt class="col-7 fw-normal text-secondary">Cổ phiếu {{ $stock->symbol }} khả dụng</dt>
                        <dd class="col-5 text-end mb-1">@money($holding?->availableQuantity() ?? 0)</dd>
                        <dt class="col-7 fw-normal text-secondary">Giá trị dự kiến</dt>
                        <dd class="col-5 text-end mb-1" id="ticket-value">—</dd>
                        <dt class="col-7 fw-normal text-secondary">Phí {{ number_format($feeRate * 100, 2, ',', '.') }}%<span id="ticket-tax-label" hidden> + thuế {{ number_format($taxRate * 100, 2, ',', '.') }}%</span></dt>
                        <dd class="col-5 text-end mb-0" id="ticket-fee">—</dd>
                    </dl>

                    <button type="submit" class="btn w-100" id="ticket-submit">Đặt lệnh</button>
                    <p class="small text-secondary mt-2 mb-0">Lệnh MP khớp ngay theo giá hiện tại. Lệnh LO được giữ tiền hoặc cổ phiếu và chờ khớp.</p>
                </form>

                @push('scripts')
                <script>
                    (function () {
                        const form = document.getElementById('order-ticket');
                        const lastPrice = {{ $stock->last_price }};
                        const feeRate = {{ $feeRate }}, taxRate = {{ $taxRate }};
                        const fmt = (n) => Math.round(n).toLocaleString('vi-VN');
                        function refresh() {
                            const side = form.querySelector('input[name=side]:checked')?.value ?? 'buy';
                            const isLimit = form.type.value === 'LO';
                            const priceInput = form.limit_price;
                            document.getElementById('limit-price-group').hidden = ! isLimit;
                            priceInput.disabled = ! isLimit;
                            const price = isLimit ? Number(priceInput.value || 0) : lastPrice;
                            const value = Number(form.quantity.value || 0) * price;
                            const fee = Math.ceil(value * feeRate) + (side === 'sell' ? Math.ceil(value * taxRate) : 0);
                            document.getElementById('ticket-value').textContent = fmt(value) + ' đ';
                            document.getElementById('ticket-fee').textContent = fmt(fee) + ' đ';
                            document.getElementById('ticket-tax-label').hidden = side !== 'sell';
                            const btn = document.getElementById('ticket-submit');
                            btn.className = 'btn w-100 ' + (side === 'buy' ? 'btn-success' : 'btn-danger');
                            btn.textContent = (side === 'buy' ? 'Đặt lệnh mua ' : 'Đặt lệnh bán ') + '{{ $stock->symbol }}';
                        }
                        form.addEventListener('input', refresh);
                        form.addEventListener('change', refresh);
                        form.addEventListener('submit', () => { document.getElementById('ticket-submit').disabled = true; });
                        refresh();
                    })();
                </script>
                @endpush
            @endif
        @endguest
    </div>
</div>
