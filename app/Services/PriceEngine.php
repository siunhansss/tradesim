<?php

namespace App\Services;

use App\Enums\StockStatus;
use App\Jobs\EvaluatePriceAlerts;
use App\Models\JobRun;
use App\Models\MarketSetting;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Random\Randomizer;
use Throwable;

/**
 * Price Engine (M02, quy trình 5.6): sinh giá mô phỏng theo bước ngẫu nhiên có giới hạn,
 * ghi lịch sử giá, rồi gọi bộ khớp lệnh LO. Mỗi lần chạy ghi một dòng job_runs.
 *
 * Quy tắc giá (docs/design/matching-rules.md):
 * - Giá mới = giá cũ × (1 + r), r ngẫu nhiên đều trong [-price_volatility, +price_volatility].
 * - Làm tròn theo bước giá HOSE, giới hạn trong trần/sàn của phiên.
 * - Chỉ cập nhật mã đang giao dịch, chỉ chạy khi phiên mô phỏng mở.
 */
class PriceEngine
{
    public const SOURCE = 'Giá mô phỏng (Price Engine)';

    public function __construct(
        private LimitOrderMatcher $matcher,
        private Randomizer $random = new Randomizer,
    ) {}

    /**
     * @return array{status: string, updated: int, fills: int, errors: int}
     */
    public function tick(string $actor = 'scheduler'): array
    {
        $run = JobRun::query()->create([
            'job' => 'tradesim:prices',
            'status' => 'running',
            'details' => ['actor' => $actor],
            'started_at' => now(),
        ]);

        if (! MarketSetting::isMarketOpen()) {
            $run->update(['status' => 'skipped', 'finished_at' => now(), 'details' => ['actor' => $actor, 'reason' => 'Phiên mô phỏng đang đóng']]);

            return ['status' => 'skipped', 'updated' => 0, 'fills' => 0, 'errors' => 0];
        }

        try {
            $updated = $this->updatePrices();
            $match = $this->matcher->run();
            // Kiểm tra cảnh báo giá qua Queue (quy trình 5.7); worker chạy bằng lịch queue:work.
            EvaluatePriceAlerts::dispatch();

            $result = ['status' => 'ok', 'updated' => $updated, 'fills' => $match['fills'], 'errors' => count($match['errors'])];
            $run->update([
                'status' => $match['errors'] ? 'failed' : 'ok',
                'finished_at' => now(),
                'details' => ['actor' => $actor, 'updated' => $updated, 'fills' => $match['fills'], 'errors' => $match['errors']],
            ]);

            return $result;
        } catch (Throwable $e) {
            // Ghi lỗi để truy vết (mục 8.3); lần chạy sau thử lại an toàn vì mỗi bước nằm trong transaction riêng.
            Log::error('Price Engine lỗi', ['exception' => $e]);
            $run->update(['status' => 'failed', 'finished_at' => now(), 'error' => $e->getMessage()]);

            return ['status' => 'failed', 'updated' => 0, 'fills' => 0, 'errors' => 1];
        }
    }

    private function updatePrices(): int
    {
        $volatility = (float) MarketSetting::value('price_volatility');
        $now = now();
        $count = 0;

        DB::transaction(function () use ($volatility, $now, &$count) {
            $stocks = Stock::query()->where('status', StockStatus::Active->value)->lockForUpdate()->get();

            foreach ($stocks as $stock) {
                $price = $this->nextPrice($stock, $volatility);
                $volume = $this->random->getInt(0, 40) * 100;

                $stock->update([
                    'last_price' => $price,
                    'last_price_at' => $now,
                    'price_source' => self::SOURCE,
                    'volume' => $stock->volume + $volume,
                ]);
                $stock->prices()->create([
                    'price' => $price,
                    'volume' => $volume,
                    'source' => self::SOURCE,
                    'recorded_at' => $now,
                ]);
                $count++;
            }
        });

        return $count;
    }

    public function nextPrice(Stock $stock, float $volatility): int
    {
        $change = ($this->random->getFloat(0, 1) * 2 - 1) * $volatility;
        $raw = $stock->last_price * (1 + $change);
        $tick = Stock::tickSize((int) $raw);
        $rounded = (int) (round($raw / $tick) * $tick);

        return max($stock->floor_price, min($stock->ceiling_price, $rounded));
    }
}
