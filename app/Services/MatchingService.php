<?php

namespace App\Services;

use App\Enums\LedgerType;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderFilled;
use App\Models\Holding;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\Wallet;
use LogicException;

/**
 * Khớp một phần hoặc toàn bộ một lệnh (mục 6.2, quy trình 5.3 bước 7, 5.4 bước 4).
 *
 * PHẢI gọi bên trong DB::transaction, khi đã khóa ví, holding và mã cổ phiếu bằng lockForUpdate.
 * Hàm này lưu order, wallet, holding, stock và tạo trade cùng các bút toán.
 */
class MatchingService
{
    public function __construct(
        private FinanceCalculator $calc,
        private WalletService $wallets,
    ) {}

    public function fill(Order $order, int $quantity, int $price, Wallet $wallet, ?Holding $holding, Stock $stock): Trade
    {
        if (! $order->isOpen() || $quantity <= 0 || $quantity > $order->remainingQuantity()) {
            throw new LogicException("Không thể khớp {$quantity} cp cho lệnh #{$order->id}.");
        }

        $isLast = $quantity === $order->remainingQuantity();

        if ($order->side === OrderSide::Buy) {
            $result = $this->calc->buy($quantity, $price);

            // Trả lại phần tiền đã giữ tương ứng phần khớp; lần khớp cuối trả toàn bộ phần còn lại.
            $basis = $order->limit_price ?? $price;
            $release = $isLast
                ? $order->reserved_amount
                : min($order->reserved_amount, $this->calc->buyReservation($quantity, $basis));
            $order->reserved_amount -= $release;
            $wallet->reserved -= $release;
            $this->wallets->record($wallet, LedgerType::Release, $release, $order, "Trả tiền giữ cho phần khớp của lệnh #{$order->id}");

            $wallet->balance -= $result['value'];
            $this->wallets->record($wallet, LedgerType::Buy, -$result['value'], $order, "Mua {$quantity} {$stock->symbol} giá {$price}");
            $wallet->balance -= $result['fee'];
            $this->wallets->record($wallet, LedgerType::Fee, -$result['fee'], $order, 'Phí mua');

            $holding ??= Holding::query()->firstOrCreate(
                ['user_id' => $order->user_id, 'stock_id' => $order->stock_id],
                ['quantity' => 0, 'reserved_quantity' => 0, 'total_cost' => 0, 'realized_pnl' => 0],
            );
            $holding->quantity += $quantity;
            $holding->total_cost += $result['cash'];
            $realized = null;
        } else {
            if ($holding === null) {
                throw new LogicException("Lệnh bán #{$order->id} không có holding.");
            }
            $result = $this->calc->sell($quantity, $price, $holding->quantity, $holding->total_cost);

            $holding->quantity -= $quantity;
            $holding->reserved_quantity -= $quantity;
            $holding->total_cost -= $result['cost_of_sold'];
            $holding->realized_pnl += $result['realized_pnl'];
            $order->reserved_quantity -= $quantity;

            $wallet->balance += $result['value'];
            $this->wallets->record($wallet, LedgerType::Sell, $result['value'], $order, "Bán {$quantity} {$stock->symbol} giá {$price}");
            $wallet->balance -= $result['fee'];
            $this->wallets->record($wallet, LedgerType::Fee, -$result['fee'], $order, 'Phí bán');
            $wallet->balance -= $result['tax'];
            $this->wallets->record($wallet, LedgerType::Tax, -$result['tax'], $order, 'Thuế bán mô phỏng');
            $realized = $result['realized_pnl'];
        }

        $previous = (float) ($order->avg_fill_price ?? 0);
        $order->avg_fill_price = round(($previous * $order->filled_quantity + $price * $quantity) / ($order->filled_quantity + $quantity), 4);
        $order->filled_quantity += $quantity;
        $next = $order->filled_quantity === $order->quantity ? OrderStatus::Filled : OrderStatus::PartiallyFilled;
        if (! $order->status->canTransitionTo($next)) {
            throw new LogicException("Lệnh #{$order->id} không thể chuyển từ {$order->status->value} sang {$next->value}.");
        }
        $order->status = $next;

        $stock->volume += $quantity;

        $order->save();
        $wallet->save();
        $holding->save();
        $stock->save();

        if ($wallet->balance < $wallet->reserved) {
            // Không bao giờ xảy ra nếu quy tắc giữ tiền đúng; ném lỗi để transaction rollback (BR-02).
            throw new LogicException("Số dư khả dụng âm sau khi khớp lệnh #{$order->id}.");
        }

        $trade = Trade::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'stock_id' => $order->stock_id,
            'side' => $order->side,
            'quantity' => $quantity,
            'price' => $price,
            'value' => $result['value'],
            'fee' => $result['fee'],
            'tax' => $result['tax'],
            'fee_rate' => $this->calc->feeRate(),
            'tax_rate' => $order->side === OrderSide::Sell ? $this->calc->sellTaxRate() : 0,
            'realized_pnl' => $realized,
            'executed_at' => now(),
        ]);

        // Thông báo khớp lệnh: event chỉ phát sau khi transaction commit (ShouldDispatchAfterCommit).
        OrderFilled::dispatch($order, $trade);

        return $trade;
    }
}
