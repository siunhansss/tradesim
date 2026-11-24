<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Trade;
use App\Support\Format;
use Illuminate\Notifications\Notification;

/** Thông báo khớp lệnh. Không chứa số dư hay tài sản (BR-20). */
class OrderFilledNotification extends Notification
{
    public function __construct(private Order $order, private Trade $trade) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $symbol = $this->order->stock()->value('symbol');
        $state = $this->order->status === OrderStatus::Filled ? 'đã khớp toàn bộ' : 'khớp một phần';

        return [
            'kind' => 'order_filled',
            'message' => "Lệnh #{$this->order->id} {$this->order->side->label()} {$symbol} {$state}: "
                .Format::money($this->trade->quantity).' cp giá '.Format::money($this->trade->price).' đ.',
            'url' => route('orders.index'),
        ];
    }
}
