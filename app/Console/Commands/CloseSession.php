<?php

namespace App\Console\Commands;

use App\Services\SessionCloser;
use Illuminate\Console\Command;

class CloseSession extends Command
{
    protected $signature = 'tradesim:close-session';

    protected $description = 'Chốt phiên: hủy lệnh LO còn mở, lấy giá đóng cửa làm giá tham chiếu mới';

    public function handle(SessionCloser $closer): int
    {
        $r = $closer->close('artisan');
        $this->line("{$r['status']}: hủy {$r['cancelled']} lệnh LO còn mở, chốt giá {$r['stocks']} mã.");

        return $r['status'] === 'ok' ? self::SUCCESS : self::FAILURE;
    }
}
