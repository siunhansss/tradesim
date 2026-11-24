<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Watchlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Danh sách theo dõi (FR-23). */
class WatchlistController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('watchlist.index', [
            'stocks' => $user->watchedStocks()->withCount(['priceAlerts as active_alerts_count' => fn ($q) => $q
                ->where('user_id', $user->id)->where('status', 'active')])->orderBy('symbol')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['stock_id' => ['required', 'integer', Rule::exists('stocks', 'id')->whereNot('status', 'hidden')]]);
        Watchlist::query()->firstOrCreate(['user_id' => $request->user()->id, 'stock_id' => $data['stock_id']]);
        $symbol = Stock::query()->whereKey($data['stock_id'])->value('symbol');

        return back()->with('success', "Đã thêm {$symbol} vào danh sách theo dõi.");
    }

    public function destroy(Request $request, Stock $stock): RedirectResponse
    {
        Watchlist::query()->where('user_id', $request->user()->id)->where('stock_id', $stock->id)->delete();

        return back()->with('success', "Đã bỏ theo dõi {$stock->symbol}.");
    }
}
