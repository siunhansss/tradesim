<?php

namespace App\Http\Controllers;

use App\Services\PortfolioService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Trang danh mục đầu tư (M05, FR-11): nắm giữ, giá vốn, lãi/lỗ, phân bổ. */
class PortfolioController extends Controller
{
    private const SORTS = ['symbol', 'value', 'unrealized', 'unrealized_percent', 'weight'];

    public function index(Request $request, PortfolioService $portfolio): View
    {
        $filters = $request->validate([
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
            'dir' => ['nullable', 'in:asc,desc'],
        ]);
        $sort = $filters['sort'] ?? 'value';
        $dir = $filters['dir'] ?? 'desc';

        $summary = $portfolio->summary($request->user());
        $rows = collect($summary['rows']);
        $open = $rows->where('quantity', '>', 0)->sortBy($sort, SORT_NATURAL, $dir === 'desc')->values();
        $closed = $rows->where('quantity', 0)->sortByDesc('realized')->values();

        // Dữ liệu biểu đồ phân bổ: từng mã và tiền mặt.
        $allocation = $open->map(fn ($r) => ['label' => $r['symbol'], 'value' => $r['value']])
            ->push(['label' => 'Tiền mặt', 'value' => $summary['cash']])
            ->filter(fn ($a) => $a['value'] > 0)->values();

        return view('portfolio.index', [
            'summary' => $summary,
            'open' => $open,
            'closed' => $closed,
            'allocation' => $allocation,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }
}
