<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\JobRun;
use App\Models\MarketSetting;
use App\Services\AuditLogger;
use App\Services\PriceEngine;
use App\Services\SessionCloser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cấu hình thị trường và tác vụ cập nhật giá (M10, quy trình 5.8). */
class SettingController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function edit(): View
    {
        $percent = fn (string $key) => rtrim(rtrim(number_format((float) MarketSetting::value($key) * 100, 3, ',', ''), '0'), ',') ?: '0';

        return view('admin.settings.edit', [
            'values' => [
                'market_open' => MarketSetting::isMarketOpen(),
                'fee_percent' => $percent('fee_rate'),
                'tax_percent' => $percent('sell_tax_rate'),
                'initial_capital' => MarketSetting::initialCapital(),
                'max_fill_per_tick' => (int) MarketSetting::value('max_fill_per_tick'),
                'volatility_percent' => $percent('price_volatility'),
            ],
            'jobs' => JobRun::query()->latest('id')->limit(10)->get(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $changes = [];
        foreach ($request->settings() as $key => $value) {
            $old = (string) MarketSetting::value($key);
            if ($old !== $value) {
                MarketSetting::put($key, $value);
                $changes[$key] = ['từ' => $old, 'thành' => $value];
            }
        }

        if (! $changes) {
            return back()->with('warning', 'Không có thay đổi nào.');
        }

        // Ghi nhật ký từng thay đổi cấu hình (FR-14, quy trình 5.8 bước 3).
        $this->audit->log('settings.update', null, 'ok', $changes);

        return redirect()->route('admin.settings.edit')->with('success', 'Đã lưu cấu hình. Phí và thuế mới chỉ áp dụng cho các lần khớp từ bây giờ.');
    }

    public function runPrices(Request $request, PriceEngine $engine): RedirectResponse
    {
        $result = $engine->tick('admin:'.$request->user()->email);
        $this->audit->log('prices.run', null, $result['status'], $result);

        return back()->with(
            $result['status'] === 'ok' ? 'success' : 'warning',
            match ($result['status']) {
                'ok' => "Đã cập nhật giá {$result['updated']} mã, {$result['fills']} lần khớp lệnh LO.",
                'skipped' => 'Phiên mô phỏng đang đóng nên không cập nhật giá. Hãy mở phiên trước.',
                default => 'Cập nhật giá gặp lỗi. Xem chi tiết trong bảng tác vụ nền bên dưới.',
            },
        );
    }

    /** Chốt phiên thủ công khi demo, thay cho lịch 14:45 (issue #12). */
    public function closeSession(Request $request, SessionCloser $closer): RedirectResponse
    {
        $result = $closer->close('admin:'.$request->user()->email);
        $this->audit->log('session.close', null, $result['status'], $result);

        return back()->with(
            $result['status'] === 'ok' ? 'success' : 'error',
            $result['status'] === 'ok'
                ? "Đã chốt phiên: hủy {$result['cancelled']} lệnh LO còn mở, đặt giá tham chiếu mới cho {$result['stocks']} mã."
                : 'Chốt phiên gặp lỗi. Xem chi tiết trong bảng tác vụ nền.',
        );
    }
}
