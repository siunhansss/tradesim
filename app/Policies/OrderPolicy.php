<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/** Phân quyền trên lệnh (quy trình 5.5 bước 1). Laravel tự nhận policy theo tên model. */
class OrderPolicy
{
    /** Chỉ chủ lệnh được hủy, và chỉ khi lệnh còn mở (chờ khớp hoặc khớp một phần). */
    public function cancel(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->isOpen();
    }
}
