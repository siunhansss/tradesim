<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;

/** Kết quả đặt lệnh trả về cho controller. */
final readonly class OrderPlacement
{
    public function __construct(
        public Order $order,
        public bool $duplicate = false,
    ) {}

    public function rejected(): bool
    {
        return $this->order->status === OrderStatus::Rejected;
    }
}
