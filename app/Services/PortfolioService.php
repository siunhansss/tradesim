<?php

namespace App\Services;

use App\Enums\LedgerType;
use App\Models\Holding;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\CarbonInterface;

/**
 * Định giá danh mục và tổng hợp tài chính (M05, M06, mục 6.2).
 * Mọi con số tính lại từ ví, holdings và giá hiện tại; không lưu trùng (BR-14).
 */
class PortfolioService
{
    public function __construct(private FinanceCalculator $calc) {}

    /**
     * @return array{
     *     rows: list<array{holding: Holding, symbol: string, name: string, quantity: int, available: int, average_cost: float,
     *                      price: int, price_class: string, value: int, cost: int, unrealized: int, unrealized_percent: float,
     *                      realized: int, weight: float, price_at: ?CarbonInterface}>,
     *     cash: int, reserved: int, available: int, stock_value: int, total_assets: int,
     *     unrealized: int, realized: int, net_invested: int, profit: int, roi: float, cash_weight: float
     * }
     */
    public function summary(User $user): array
    {
        $wallet = $user->wallet()->first();
        $cash = $wallet?->balance ?? 0;

        $holdings = Holding::query()
            ->with('stock')
            ->where('user_id', $user->id)
            ->where(fn ($q) => $q->where('quantity', '>', 0)->orWhere('realized_pnl', '!=', 0))
            ->get();

        $rows = $holdings->map(function (Holding $h) {
            // Giá định giá: giá khớp mô phỏng gần nhất (mục 6.2).
            $price = $h->stock->last_price;
            $value = $h->quantity * $price;
            $unrealized = $h->quantity > 0 ? $value - $h->total_cost : 0;

            return [
                'holding' => $h,
                'symbol' => $h->stock->symbol,
                'name' => $h->stock->name,
                'quantity' => $h->quantity,
                'available' => $h->availableQuantity(),
                'average_cost' => $h->averageCost(),
                'price' => $price,
                'price_class' => $h->stock->priceClass(),
                'value' => $value,
                'cost' => $h->total_cost,
                'unrealized' => $unrealized,
                'unrealized_percent' => $h->total_cost > 0 ? $unrealized / $h->total_cost * 100 : 0.0,
                'realized' => $h->realized_pnl,
                'weight' => 0.0,
                'price_at' => $h->stock->last_price_at,
            ];
        })->sortByDesc('value')->values();

        $stockValue = (int) $rows->sum('value');
        $total = $cash + $stockValue;
        $rows = $rows->map(fn ($r) => [...$r, 'weight' => $total > 0 ? $r['value'] / $total * 100 : 0.0]);

        // Vốn đầu tư ròng = vốn khởi tạo + các điều chỉnh vốn có kiểm toán (mục 6.2).
        $netInvested = $wallet
            ? (int) WalletTransaction::query()->where('wallet_id', $wallet->id)
                ->whereIn('type', [LedgerType::Init->value, LedgerType::Adjust->value])->sum('amount')
            : 0;

        return [
            'rows' => $rows->all(),
            'cash' => $cash,
            'reserved' => $wallet?->reserved ?? 0,
            'available' => $wallet?->available() ?? 0,
            'stock_value' => $stockValue,
            'total_assets' => $total,
            'unrealized' => (int) $rows->sum('unrealized'),
            'realized' => (int) $rows->sum('realized'),
            'net_invested' => $netInvested,
            'profit' => $total - $netInvested,
            'roi' => $this->calc->roi($total, $netInvested),
            'cash_weight' => $total > 0 ? $cash / $total * 100 : 0.0,
        ];
    }
}
