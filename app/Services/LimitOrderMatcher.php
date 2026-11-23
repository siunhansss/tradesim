<?php

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\StockStatus;
use App\Models\Holding;
use App\Models\MarketSetting;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Khớp lệnh LO đang chờ theo giá mô phỏng (quy trình 5.6 bước 4).
 *
 * Quy tắc khớp (docs/design/matching-rules.md):
 * - Lệnh mua khớp khi giá hiện tại ≤ giá đặt; lệnh bán khớp khi giá hiện tại ≥ giá đặt.
 * - Giá khớp là giá hiện tại (không kém hơn giá đặt).
 * - Mỗi lần chạy, một lệnh khớp tối đa max_fill_per_tick cổ phiếu (mặc định 1.000);
 *   phần còn lại chờ lần sau, tạo ra trạng thái khớp một phần.
 * - Mỗi lệnh khớp trong transaction riêng; lỗi ở một lệnh không ảnh hưởng lệnh khác.
 */
class LimitOrderMatcher
{
    public function __construct(private MatchingService $matching) {}

    /**
     * @return array{fills: int, errors: list<string>}
     */
    public function run(): array
    {
        $maxFill = max(100, intdiv((int) MarketSetting::value('max_fill_per_tick'), 100) * 100);
        $fills = 0;
        $errors = [];

        $candidates = Order::query()
            ->select('orders.id')
            ->join('stocks', 'stocks.id', '=', 'orders.stock_id')
            ->where('orders.type', OrderType::Limit->value)
            ->whereIn('orders.status', [OrderStatus::Pending->value, OrderStatus::PartiallyFilled->value])
            ->where('stocks.status', StockStatus::Active->value)
            ->where(fn ($q) => $q
                ->where(fn ($b) => $b->where('orders.side', OrderSide::Buy->value)->whereColumn('stocks.last_price', '<=', 'orders.limit_price'))
                ->orWhere(fn ($s) => $s->where('orders.side', OrderSide::Sell->value)->whereColumn('stocks.last_price', '>=', 'orders.limit_price')))
            ->orderBy('orders.id')   // lệnh đặt trước được khớp trước
            ->pluck('orders.id');

        foreach ($candidates as $orderId) {
            try {
                $fills += DB::transaction(fn () => $this->matchOne($orderId, $maxFill)) ? 1 : 0;
            } catch (Throwable $e) {
                $errors[] = "Lệnh #{$orderId}: {$e->getMessage()}";
            }
        }

        return ['fills' => $fills, 'errors' => $errors];
    }

    /** Khớp một lệnh; trả về false nếu điều kiện không còn thỏa sau khi khóa. */
    private function matchOne(int $orderId, int $maxFill): bool
    {
        $order = Order::query()->whereKey($orderId)->lockForUpdate()->first();
        if (! $order || ! $order->isOpen()) {
            return false;
        }

        // Thứ tự khóa giống OrderService: mã, ví, holding.
        $stock = Stock::query()->whereKey($order->stock_id)->lockForUpdate()->first();
        $wallet = Wallet::query()->where('user_id', $order->user_id)->lockForUpdate()->first();
        $holding = Holding::query()->where('user_id', $order->user_id)->where('stock_id', $order->stock_id)->lockForUpdate()->first();

        $price = $stock->last_price;
        $ok = $order->side === OrderSide::Buy ? $price <= $order->limit_price : $price >= $order->limit_price;
        if (! $ok || ! $stock->isTradable()) {
            return false;
        }

        $quantity = min($order->remainingQuantity(), $maxFill);
        $this->matching->fill($order, $quantity, $price, $wallet, $holding, $stock);

        return true;
    }
}
