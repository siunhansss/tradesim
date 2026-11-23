@extends('layouts.app')

@section('title', 'Cấu hình thị trường')

@section('content')
@include('admin._nav')

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h1 class="h5 mb-3">Cấu hình thị trường mô phỏng</h1>
                <form method="POST" action="{{ route('admin.settings.update') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="market_open" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="market_open" name="market_open" value="1" @checked(old('market_open', $values['market_open']))>
                        <label class="form-check-label" for="market_open"><strong>Phiên mô phỏng đang mở</strong></label>
                        <div class="form-text">Khi đóng phiên, lệnh mới bị từ chối và Price Engine không cập nhật giá (BR-04).</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="fee_percent" class="form-label">Phí giao dịch (%)</label>
                            <input id="fee_percent" name="fee_percent" inputmode="decimal" value="{{ old('fee_percent', $values['fee_percent']) }}" class="form-control num @error('fee_percent') is-invalid @enderror">
                            <div class="form-text">Áp dụng cho cả mua và bán. Ví dụ 0,15.</div>
                            @error('fee_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="tax_percent" class="form-label">Thuế bán mô phỏng (%)</label>
                            <input id="tax_percent" name="tax_percent" inputmode="decimal" value="{{ old('tax_percent', $values['tax_percent']) }}" class="form-control num @error('tax_percent') is-invalid @enderror">
                            <div class="form-text">Ví dụ 0,1. Nhập 0 để tắt.</div>
                            @error('tax_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="initial_capital" class="form-label">Vốn khởi tạo (đ)</label>
                            <input id="initial_capital" name="initial_capital" type="number" step="1000" value="{{ old('initial_capital', $values['initial_capital']) }}" class="form-control num @error('initial_capital') is-invalid @enderror">
                            <div class="form-text">Chỉ áp dụng cho tài khoản đăng ký sau khi lưu.</div>
                            @error('initial_capital')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="max_fill_per_tick" class="form-label">Khớp tối đa mỗi lần (cp)</label>
                            <input id="max_fill_per_tick" name="max_fill_per_tick" type="number" step="100" value="{{ old('max_fill_per_tick', $values['max_fill_per_tick']) }}" class="form-control num @error('max_fill_per_tick') is-invalid @enderror">
                            <div class="form-text">Khối lượng tối đa một lệnh LO được khớp mỗi lần cập nhật giá.</div>
                            @error('max_fill_per_tick')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="volatility_percent" class="form-label">Biên dao động giá mỗi lần (%)</label>
                            <input id="volatility_percent" name="volatility_percent" inputmode="decimal" value="{{ old('volatility_percent', $values['volatility_percent']) }}" class="form-control num @error('volatility_percent') is-invalid @enderror">
                            <div class="form-text">Từ 0,1 đến 7.</div>
                            @error('volatility_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <p class="small text-secondary mt-3 mb-0">Mọi thay đổi được ghi vào nhật ký kiểm toán. Phí và thuế đã áp dụng cho giao dịch cũ không thay đổi (BR-13).</p>
                    <button type="submit" class="btn btn-primary mt-3">Lưu cấu hình</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6">Cập nhật giá ngay</h2>
                <p class="small text-secondary">Chạy Price Engine một lần và khớp các lệnh LO thỏa điều kiện, không cần chờ lịch 30 giây.</p>
                <form method="POST" action="{{ route('admin.prices.run') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">Chạy cập nhật giá</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white fw-semibold">Tác vụ nền gần nhất</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Bắt đầu</th><th>Kết quả</th><th>Chi tiết</th></tr></thead>
                    <tbody>
                    @forelse ($jobs as $job)
                        <tr>
                            <td class="num small">@datetime($job->started_at)</td>
                            <td><span class="badge text-bg-{{ ['ok' => 'success', 'skipped' => 'secondary', 'failed' => 'danger'][$job->status] ?? 'warning' }}">{{ $job->status }}</span></td>
                            <td class="small">
                                @if ($job->status === 'ok')
                                    {{ $job->details['updated'] ?? 0 }} mã · {{ $job->details['fills'] ?? 0 }} lần khớp
                                @elseif ($job->status === 'skipped')
                                    {{ $job->details['reason'] ?? '' }}
                                @else
                                    {{ $job->error ?? implode('; ', $job->details['errors'] ?? []) }}
                                @endif
                                <span class="text-secondary">· {{ $job->details['actor'] ?? '' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-secondary py-3">Chưa có lần chạy nào.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
