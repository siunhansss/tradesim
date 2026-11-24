<?php

namespace App\Notifications;

use App\Models\PriceAlert;
use App\Support\Format;
use Illuminate\Notifications\Notification;

/** Thông báo cảnh báo giá. Không chứa số dư hay tài sản (BR-20). */
class PriceAlertTriggered extends Notification
{
    public function __construct(private PriceAlert $alert, private string $symbol) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'price_alert',
            'message' => "{$this->symbol} chạm ngưỡng: giá ".Format::money($this->alert->triggered_price).' đ ('
                .mb_strtolower($this->alert->condition->label()).' '.Format::money($this->alert->target_price).' đ).',
            'url' => route('market.show', $this->symbol),
        ];
    }
}
