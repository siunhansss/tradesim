<?php

namespace App\Services;

use App\Enums\LedgerType;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Holding;
use App\Models\MarketSetting;
use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use App\Models\Wallet;
use App\Support\Format;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Đặt lệnh (M04, quy trình 5.3 và 5.4, sơ đồ docs/design/order-sequence).
 *
 * Mọi thay đổi tiền, cổ phiếu và trạng thái lệnh nằm trong MỘT transaction (BR-07).
 * Thứ tự khóa hàng cố định: ví trước, holding sau, để tránh deadlock.
 */
class OrderService
{
    public function __construct(
        private FinanceCalculator $calc,
        private MatchingService $matching,
        private WalletService $wallets,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array{side: string, type: string, quantity: int|string, limit_price?: int|string|null, request_key: string}  $data
     */
    public function place(User $user, Stock $stock, array $data): OrderPlacement
    {
        // Gửi lại cùng request_key: trả về lệnh cũ, không tạo lệnh mới (BR-08).
        if ($existing = $this->findByRequestKey($user, $data['request_key'])) {
            return new OrderPlacement($existing, duplicate: true);
        }

        try {
            return DB::transaction(fn () => $this->placeLocked($user, $stock, $data));
        } catch (UniqueConstraintViolationException) {
            // Hai yêu cầu cùng request_key đến gần như cùng lúc: yêu cầu sau thua ở khóa duy nhất.
            return new OrderPlacement($this->findByRequestKey($user, $data['request_key']), duplicate: true);
        }
    }

    /**
     * Kiểm tra điều kiện giao dịch không phụ thuộc số dư (BR-04).
     * Trả về lý do từ chối, hoặc null nếu hợp lệ.
     */
    public function preTradeCheck(User $user, Stock $stock, OrderType $type, ?int $limitPrice): ?string
    {
        return match (true) {
            ! $user->isTrader() => 'Chỉ tài khoản nhà đầu tư được đặt lệnh.',
            ! $user->isActive() => 'Tài khoản đang bị khóa giao dịch.',
            ! MarketSetting::isMarketOpen() => 'Thị trường mô phỏng đang đóng cửa.',
            ! $stock->isTradable() => "Mã {$stock->symbol} đang ngừng giao dịch.",
            $type === OrderType::Limit && ($limitPrice > $stock->ceiling_price || $limitPrice < $stock->floor_price) => 'Giá đặt phải nằm trong biên độ '.Format::money($stock->floor_price).' – '.Format::money($stock->ceiling_price).' đ.',
            default => null,
        };
    }

    private function placeLocked(User $user, Stock $stock, array $data): OrderPlacement
    {
        $stock = Stock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
        $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();

        $side = OrderSide::from($data['side']);
        $type = OrderType::from($data['type']);
        $quantity = (int) $data['quantity'];
        $limitPrice = $type === OrderType::Limit ? (int) $data['limit_price'] : null;
        // Lệnh MP giữ tiền và khớp theo giá hiện tại; lệnh LO giữ tiền theo giá đặt.
        $price = $limitPrice ?? $stock->last_price;

        $holding = Holding::query()
            ->where('user_id', $user->id)->where('stock_id', $stock->id)
            ->lockForUpdate()->first();

        $order = new Order([
            'user_id' => $user->id,
            'stock_id' => $stock->id,
            'side' => $side,
            'type' => $type,
            'quantity' => $quantity,
            'limit_price' => $limitPrice,
            'status' => OrderStatus::Pending,
            'request_key' => $data['request_key'],
        ]);

        $reason = $this->preTradeCheck($user, $stock, $type, $limitPrice)
            ?? ($side === OrderSide::Buy
                ? $this->checkCash($wallet, $quantity, $price)
                : $this->checkShares($holding, $quantity));

        if ($reason !== null) {
            $order->status = OrderStatus::Rejected;
            $order->reject_reason = $reason;
            $order->save();
            $this->audit->log('order.place', $order, 'denied', ['reason' => $reason], $user);

            return new OrderPlacement($order);
        }

        // Giữ trước tài sản cho lệnh (BR-05): tiền với lệnh mua, cổ phiếu với lệnh bán.
        if ($side === OrderSide::Buy) {
            $reserve = $this->calc->buyReservation($quantity, $price);
            $order->reserved_amount = $reserve;
            $order->save();
            $wallet->reserved += $reserve;
            $this->wallets->record($wallet, LedgerType::Hold, -$reserve, $order, "Giữ tiền cho lệnh mua #{$order->id}");
        } else {
            $order->reserved_quantity = $quantity;
            $order->save();
            $holding->reserved_quantity += $quantity;
            $holding->save();
        }
        $wallet->save();

        if ($type === OrderType::Market) {
            $this->matching->fill($order, $quantity, $stock->last_price, $wallet, $holding, $stock);
        }

        $this->audit->log('order.place', $order, 'ok', [
            'side' => $side->value, 'type' => $type->value, 'quantity' => $quantity,
            'price' => $limitPrice, 'status' => $order->status->value,
        ], $user);

        return new OrderPlacement($order);
    }

    private function checkCash(Wallet $wallet, int $quantity, int $price): ?string
    {
        $need = $this->calc->buyReservation($quantity, $price);

        return $need > $wallet->available()
            ? 'Số dư khả dụng không đủ: cần '.Format::money($need).' đ, khả dụng '.Format::money($wallet->available()).' đ.'
            : null;
    }

    private function checkShares(?Holding $holding, int $quantity): ?string
    {
        $available = $holding?->availableQuantity() ?? 0;

        return $quantity > $available
            ? 'Khối lượng bán vượt số cổ phiếu khả dụng ('.Format::money($available).' cp).'
            : null;
    }

    private function findByRequestKey(User $user, string $key): ?Order
    {
        return Order::query()->where('user_id', $user->id)->where('request_key', $key)->first();
    }
}
