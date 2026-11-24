<?php

namespace App\Listeners;

use App\Events\OrderFilled;
use App\Models\User;
use App\Notifications\OrderFilledNotification;

/** Laravel tự đăng ký listener này nhờ kiểu tham số của handle(). */
class SendOrderFilledNotification
{
    public function handle(OrderFilled $event): void
    {
        // Truy vấn trực tiếp thay vì \$order->user để không vi phạm preventLazyLoading.
        User::query()->find($event->order->user_id)?->notify(new OrderFilledNotification($event->order, $event->trade));
    }
}
