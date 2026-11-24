<?php

namespace App\Policies;

use App\Models\PriceAlert;
use App\Models\User;

class PriceAlertPolicy
{
    /** Chỉ chủ cảnh báo được sửa, bật/tắt hoặc xóa. */
    public function update(User $user, PriceAlert $alert): bool
    {
        return $alert->user_id === $user->id;
    }

    public function delete(User $user, PriceAlert $alert): bool
    {
        return $alert->user_id === $user->id;
    }
}
