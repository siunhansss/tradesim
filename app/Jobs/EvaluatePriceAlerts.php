<?php

namespace App\Jobs;

use App\Enums\AlertStatus;
use App\Models\PriceAlert;
use App\Notifications\PriceAlertTriggered;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Kiểm tra cảnh báo giá sau mỗi lần cập nhật giá (quy trình 5.7).
 * ShouldBeUnique: không có hai job cùng chạy, tránh gửi trùng thông báo.
 */
class EvaluatePriceAlerts implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public function handle(): void
    {
        $alerts = PriceAlert::query()
            ->with(['stock:id,symbol,last_price', 'user'])
            ->where('status', AlertStatus::Active->value)
            ->get();

        foreach ($alerts as $alert) {
            $price = $alert->stock->last_price;
            if (! $alert->condition->isMet($price, $alert->target_price)) {
                continue;
            }

            // Cập nhật có điều kiện: chỉ một tiến trình chuyển được active -> triggered (BR-19).
            $updated = PriceAlert::query()->whereKey($alert->id)->where('status', AlertStatus::Active->value)
                ->update(['status' => AlertStatus::Triggered->value, 'triggered_at' => now(), 'triggered_price' => $price]);

            if ($updated === 1) {
                $alert->refresh();
                $alert->user->notify(new PriceAlertTriggered($alert, $alert->stock->symbol));
            }
        }
    }
}
