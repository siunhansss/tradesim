<?php

namespace App\Http\Controllers;

use App\Enums\AlertStatus;
use App\Enums\StockStatus;
use App\Http\Requests\PriceAlertRequest;
use App\Models\PriceAlert;
use App\Models\Stock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Cảnh báo giá (FR-24): thêm, xem, sửa, bật/tắt, xóa. */
class PriceAlertController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', Rule::enum(AlertStatus::class)]]);

        return view('alerts.index', [
            'alerts' => PriceAlert::query()->with('stock:id,symbol,name,last_price')
                ->where('user_id', $request->user()->id)
                ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
                ->latest('id')->paginate(config('tradesim.per_page'))->withQueryString(),
            'stocks' => Stock::query()->where('status', StockStatus::Active->value)->orderBy('symbol')->get(['id', 'symbol', 'last_price']),
            'filters' => $filters,
            'selected' => $request->integer('stock_id') ?: null,
        ]);
    }

    public function store(PriceAlertRequest $request): RedirectResponse
    {
        $request->user()->priceAlerts()->create([...$request->validated(), 'status' => AlertStatus::Active]);

        return redirect()->route('alerts.index')->with('success', 'Đã tạo cảnh báo giá. Bạn sẽ nhận thông báo khi giá chạm ngưỡng.');
    }

    public function edit(PriceAlert $alert): View
    {
        Gate::authorize('update', $alert);

        return view('alerts.edit', ['alert' => $alert->load('stock:id,symbol,last_price')]);
    }

    public function update(PriceAlertRequest $request, PriceAlert $alert): RedirectResponse
    {
        Gate::authorize('update', $alert);
        $data = $request->validated();
        // Bật lại cảnh báo đã kích hoạt: xóa thông tin lần kích hoạt trước để có thể kích hoạt lần nữa (BR-19).
        if ($data['status'] === AlertStatus::Active->value) {
            $data += ['triggered_at' => null, 'triggered_price' => null];
        }
        $alert->update($data);

        return redirect()->route('alerts.index')->with('success', 'Đã cập nhật cảnh báo.');
    }

    public function destroy(PriceAlert $alert): RedirectResponse
    {
        Gate::authorize('delete', $alert);
        $alert->delete();

        return back()->with('success', 'Đã xóa cảnh báo.');
    }
}
