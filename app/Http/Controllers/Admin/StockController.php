<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockRequest;
use App\Models\Stock;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Quản lý mã cổ phiếu (M10, quy trình 5.8). Đây là màn hình CRUD mẫu:
 * tìm kiếm, lọc, sắp xếp, phân trang, kiểm tra dữ liệu, ghi nhật ký.
 * Các màn hình quản trị khác làm theo cùng khuôn mẫu này.
 */
class StockController extends Controller
{
    private const SORTS = ['symbol', 'name', 'reference_price', 'status', 'created_at'];

    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:'.implode(',', array_column(StockStatus::cases(), 'value'))],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
            'dir' => ['nullable', 'in:asc,desc'],
        ]);

        $stocks = Stock::query()
            ->search($filters['q'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy($filters['sort'] ?? 'symbol', $filters['dir'] ?? 'asc')
            ->paginate(config('tradesim.per_page'))
            ->withQueryString();

        return view('admin.stocks.index', [
            'stocks' => $stocks,
            'filters' => $filters,
            'statuses' => StockStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.stocks.create', ['stock' => new Stock(['exchange' => 'HOSE', 'status' => StockStatus::Active])]);
    }

    public function store(StockRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $stock = Stock::query()->create([
            ...$data,
            ...Stock::priceBand((int) $data['reference_price']),
            'last_price' => $data['reference_price'],
            'last_price_at' => now(),
            'price_source' => 'Nhập bởi quản trị viên',
        ]);

        $this->audit->log('stock.create', $stock, 'ok', $data);

        return redirect()->route('admin.stocks.index')->with('success', "Đã thêm mã {$stock->symbol}.");
    }

    public function edit(Stock $stock): View
    {
        return view('admin.stocks.edit', ['stock' => $stock]);
    }

    public function update(StockRequest $request, Stock $stock): RedirectResponse
    {
        $data = $request->validated();
        $stock->fill($data);

        if ($stock->isDirty('reference_price')) {
            // Đổi giá tham chiếu thì tính lại trần/sàn và kéo giá hiện tại vào biên độ mới.
            $band = Stock::priceBand((int) $data['reference_price']);
            $stock->fill($band);
            $stock->last_price = max($band['floor_price'], min($band['ceiling_price'], $stock->last_price));
        }

        $changes = $stock->getDirty();
        $stock->save();
        $this->audit->log('stock.update', $stock, 'ok', $changes);

        return redirect()->route('admin.stocks.index')->with('success', "Đã cập nhật mã {$stock->symbol}.");
    }

    public function destroy(Stock $stock): RedirectResponse
    {
        // Không xóa mã đã phát sinh giao dịch để giữ lịch sử (BR-06); chuyển sang ngừng hiển thị.
        if ($stock->hasTradingHistory()) {
            $this->audit->log('stock.delete', $stock, 'denied', ['reason' => 'Đã có lịch sử giao dịch']);

            return back()->with('error', "Không thể xóa {$stock->symbol} vì mã đã có lệnh hoặc giao dịch. Hãy đổi trạng thái sang “Ngừng hiển thị”.");
        }

        $symbol = $stock->symbol;
        $this->audit->log('stock.delete', $stock, 'ok', ['symbol' => $symbol]);
        $stock->delete();

        return redirect()->route('admin.stocks.index')->with('success', "Đã xóa mã {$symbol}.");
    }
}
