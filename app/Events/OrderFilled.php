<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Trade;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Phát sau mỗi lần khớp lệnh; chỉ phát khi transaction khớp lệnh đã commit thành công. */
class OrderFilled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public Order $order, public Trade $trade) {}
}
