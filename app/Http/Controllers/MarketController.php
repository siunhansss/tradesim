<?php

namespace App\Http\Controllers;

use App\Enums\StockStatus;
use App\Models\MarketSetting;
use App\Models\Stock;
use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Bảng giá và chi tiết mã (M02, quy trình 5.2). Khách cũng xem được. */
class MarketController extends Controller
{
    private const SORTS = ['symbol', 'last_price', 'change', 'volume'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:50'],
            'industry' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
            'dir' => ['nullable', 'in:asc,desc'],
        ]);
        $sort = $filters['sort'] ?? 'symbol';
        $dir = $filters['dir'] ?? 'asc';

        $stocks = Stock::query()
            ->visible()
            ->search($filters['q'] ?? null)
            ->when($filters['industry'] ?? null, fn ($q, $industry) => $q->where('industry', $industry))
            ->when(
                $sort === 'change',
                fn ($q) => $q->orderByRaw('(last_price - reference_price) * 1.0 / reference_price '.$dir),
                fn ($q) => $q->orderBy($sort, $dir),
            )
            ->paginate(config('tradesim.per_page'))
            ->withQueryString();

        $lastUpdate = Stock::query()->visible()->max('last_price_at');

        return view('market.index', [
            'stocks' => $stocks,
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
            'industries' => Stock::query()->visible()->whereNotNull('industry')->distinct()->orderBy('industry')->pluck('industry'),
            'lastUpdate' => $lastUpdate ? Carbon::parse($lastUpdate) : null,
            'marketOpen' => MarketSetting::isMarketOpen(),
        ]);
    }

    public function show(Request $request, Stock $stock): View
    {
        abort_if($stock->status === StockStatus::Hidden, 404);

        $history = $stock->prices()->orderByDesc('recorded_at')->limit(60)->get()->reverse()->values();

        // Thông tin cho phiếu đặt lệnh của Trader.
        $user = $request->user();
        $canTrade = $user?->can('trade') ?? false;

        return view('market.show', [
            'stock' => $stock,
            'canTrade' => $canTrade,
            'wallet' => $canTrade ? $user->wallet()->first() : null,
            'holding' => $canTrade ? $user->holdings()->where('stock_id', $stock->id)->first() : null,
            'marketOpen' => MarketSetting::isMarketOpen(),
            'feeRate' => (float) MarketSetting::value('fee_rate'),
            'taxRate' => (float) MarketSetting::value('sell_tax_rate'),
            'chartLabels' => $history->map(fn ($p) => Format::datetime($p->recorded_at, 'd/m H:i'))->all(),
            'chartPrices' => $history->pluck('price')->all(),
        ]);
    }
}
