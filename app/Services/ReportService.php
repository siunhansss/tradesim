<?php

namespace App\Services;

use App\Enums\OrderSide;
use App\Models\PortfolioSnapshot;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Báo cáo tháng (quy trình 5.9d, FR-20). Mọi số liệu tính từ trades và portfolio_snapshots (BR-14). */
class ReportService
{
    public function __construct(private FinanceCalculator $calc) {}

    /**
     * @param  string  $month  dạng Y-m, theo giờ Việt Nam
     */
    public function monthly(User $user, string $month): array
    {
        $tz = config('tradesim.display_timezone');
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01', $tz)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // snapshot_date là ngày theo giờ Việt Nam, lưu không kèm múi giờ; so sánh bằng Carbon cùng định dạng.
        $dayFrom = Carbon::createFromFormat('Y-m-d', $start->toDateString(), 'UTC')->startOfDay();
        $dayTo = Carbon::createFromFormat('Y-m-d', $end->toDateString(), 'UTC')->endOfDay();
        $snapshots = PortfolioSnapshot::query()->where('user_id', $user->id)
            ->whereBetween('snapshot_date', [$dayFrom, $dayTo])
            ->orderBy('snapshot_date')->get();
        $before = PortfolioSnapshot::query()->where('user_id', $user->id)
            ->where('snapshot_date', '<', $dayFrom)->orderByDesc('snapshot_date')->first();

        $opening = $before?->total_assets ?? $snapshots->first()?->total_assets;
        $closing = $snapshots->last()?->total_assets;

        $trades = Trade::query()->with('stock:id,symbol')->where('user_id', $user->id)
            ->whereBetween('executed_at', [$start->copy()->utc(), $end->copy()->utc()])
            ->orderBy('executed_at')->orderBy('id')->get();

        $buys = $trades->where('side', OrderSide::Buy);
        $sells = $trades->where('side', OrderSide::Sell);
        $bySymbol = $sells->groupBy(fn (Trade $t) => $t->stock->symbol)
            ->map(fn ($g, $symbol) => ['symbol' => $symbol, 'realized' => (int) $g->sum('realized_pnl')])
            ->sortByDesc('realized')->values();

        return [
            'month' => $month,
            'start' => $start,
            'end' => $end,
            'opening' => $opening,
            'closing' => $closing,
            'roi' => $opening && $closing !== null ? ($closing - $opening) / $opening * 100 : null,
            'mdd' => $this->calc->maxDrawdown($snapshots->pluck('total_assets')->all()),
            'snapshot_days' => $snapshots->count(),
            'trade_count' => $trades->count(),
            'buy_value' => (int) $buys->sum('value'),
            'sell_value' => (int) $sells->sum('value'),
            'fees' => (int) $trades->sum('fee'),
            'taxes' => (int) $trades->sum('tax'),
            'realized' => (int) $sells->sum('realized_pnl'),
            'best' => $bySymbol->first(fn ($r) => $r['realized'] > 0),
            'worst' => $bySymbol->reverse()->first(fn ($r) => $r['realized'] < 0),
            'trades' => $trades,
        ];
    }

    /** Dữ liệu CSV: phần tổng hợp rồi danh sách giao dịch. Số liệu giống hệt trang báo cáo. */
    public function csvRows(array $r): array
    {
        $rows = [
            ['Báo cáo tháng', $r['month']],
            ['Tổng tài sản đầu kỳ (đ)', $r['opening'] ?? ''],
            ['Tổng tài sản cuối kỳ (đ)', $r['closing'] ?? ''],
            ['ROI trong kỳ (%)', $r['roi'] === null ? '' : round($r['roi'], 2)],
            ['Mức sụt giảm tối đa (%)', round($r['mdd'], 2)],
            ['Số lần khớp', $r['trade_count']],
            ['Tổng giá trị mua (đ)', $r['buy_value']],
            ['Tổng giá trị bán (đ)', $r['sell_value']],
            ['Tổng phí (đ)', $r['fees']],
            ['Tổng thuế (đ)', $r['taxes']],
            ['Lãi/lỗ đã thực hiện (đ)', $r['realized']],
            [],
            ['Thời gian', 'Mã', 'Chiều', 'Khối lượng', 'Giá', 'Giá trị', 'Phí', 'Thuế', 'Lãi/lỗ đã thực hiện'],
        ];
        foreach ($r['trades'] as $t) {
            $rows[] = [
                $t->executed_at->copy()->setTimezone(config('tradesim.display_timezone'))->format('Y-m-d H:i:s'),
                $t->stock->symbol, $t->side->label(), $t->quantity, $t->price, $t->value, $t->fee, $t->tax, $t->realized_pnl ?? '',
            ];
        }

        return $rows;
    }
}
