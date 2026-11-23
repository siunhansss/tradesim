<?php

namespace App\Http\Controllers;

use App\Enums\OrderSide;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Lịch sử giao dịch khớp (FR-12). Chỉ đọc; giao dịch khớp không sửa, không xóa (BR-06). */
class TradeController extends Controller
{
    private const SORTS = ['executed_at', 'quantity', 'price', 'value', 'realized_pnl'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:10'],
            'side' => ['nullable', 'in:'.implode(',', array_column(OrderSide::cases(), 'value'))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
            'dir' => ['nullable', 'in:asc,desc'],
        ]);
        $tz = config('tradesim.display_timezone');

        $query = Trade::query()
            ->where('user_id', $request->user()->id)
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->whereHas('stock', fn ($s) => $s->where('symbol', 'like', '%'.strtoupper($term).'%')))
            ->when($filters['side'] ?? null, fn ($q, $side) => $q->where('side', $side))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('executed_at', '>=', Carbon::parse($from, $tz)->startOfDay()->utc()))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('executed_at', '<=', Carbon::parse($to, $tz)->endOfDay()->utc()));

        // Tổng theo bộ lọc hiện tại, tính trên toàn bộ kết quả chứ không chỉ trang đang xem.
        $totals = (clone $query)->selectRaw('COUNT(*) as count, COALESCE(SUM(value), 0) as value, COALESCE(SUM(fee), 0) as fee, COALESCE(SUM(tax), 0) as tax, COALESCE(SUM(realized_pnl), 0) as realized')->first();

        $trades = $query->with('stock:id,symbol,name')
            ->orderBy($filters['sort'] ?? 'executed_at', $filters['dir'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate(config('tradesim.per_page'))
            ->withQueryString();

        return view('trades.index', [
            'trades' => $trades,
            'filters' => $filters,
            'totals' => $totals,
            'sort' => $filters['sort'] ?? 'executed_at',
            'dir' => $filters['dir'] ?? 'desc',
        ]);
    }
}
