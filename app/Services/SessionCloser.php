<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\StockStatus;
use App\Models\JobRun;
use App\Models\Order;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Chốt phiên cuối ngày (quy trình 5.6, docs/design/matching-rules.md):
 * 1. Hủy phần chưa khớp của mọi lệnh LO còn mở và giải phóng tài sản giữ.
 * 2. Ghi giá đóng cửa vào lịch sử giá.
 * 3. Giá đóng cửa thành giá tham chiếu phiên sau; tính lại trần/sàn; đặt lại tổng khối lượng.
 * 4. Chụp ảnh danh mục cuối ngày cho mọi nhà đầu tư (quy trình 5.9c).
 * Chạy lại trong cùng ngày vẫn an toàn: không còn lệnh mở để hủy, giá tham chiếu không đổi,
 * ảnh chụp của ngày được cập nhật chứ không tạo mới (BR-16).
 */
class SessionCloser
{
    public const CLOSE_SOURCE = 'Giá đóng cửa';

    public function __construct(
        private OrderService $orders,
        private PortfolioSnapshotter $snapshotter,
    ) {}

    /**
     * @return array{status: string, cancelled: int, stocks: int}
     */
    public function close(string $actor = 'scheduler'): array
    {
        $run = JobRun::query()->create([
            'job' => 'tradesim:close-session',
            'status' => 'running',
            'details' => ['actor' => $actor],
            'started_at' => now(),
        ]);

        try {
            $cancelled = 0;
            $openOrders = Order::query()
                ->where('type', OrderType::Limit->value)
                ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::PartiallyFilled->value])
                ->orderBy('id')->get();
            foreach ($openOrders as $order) {
                $cancelled += $this->orders->cancel($order, null, 'Hết phiên giao dịch') ? 1 : 0;
            }

            $stocks = DB::transaction(function () {
                $now = now();
                $list = Stock::query()->where('status', '!=', StockStatus::Hidden->value)->lockForUpdate()->get();
                foreach ($list as $stock) {
                    $close = $stock->last_price;
                    $stock->prices()->create([
                        'price' => $close, 'volume' => $stock->volume, 'source' => self::CLOSE_SOURCE, 'recorded_at' => $now,
                    ]);
                    $stock->update([
                        'reference_price' => $close,
                        ...Stock::priceBand($close),
                        'volume' => 0,
                        'price_source' => self::CLOSE_SOURCE,
                        'last_price_at' => $now,
                    ]);
                }

                return $list->count();
            });

            $run->update(['status' => 'ok', 'finished_at' => now(), 'details' => ['actor' => $actor, 'cancelled' => $cancelled, 'stocks' => $stocks]]);
            $this->snapshotter->take(null, $actor);

            return ['status' => 'ok', 'cancelled' => $cancelled, 'stocks' => $stocks];
        } catch (Throwable $e) {
            Log::error('Chốt phiên lỗi', ['exception' => $e]);
            $run->update(['status' => 'failed', 'finished_at' => now(), 'error' => $e->getMessage()]);

            return ['status' => 'failed', 'cancelled' => 0, 'stocks' => 0];
        }
    }
}
