<?php

namespace App\Console\Commands;

use App\Services\PriceEngine;
use Illuminate\Console\Command;

class UpdatePrices extends Command
{
    protected $signature = 'tradesim:prices {--times=1 : Số lần cập nhật liên tiếp} {--sleep=0 : Số giây nghỉ giữa các lần}';

    protected $description = 'Sinh giá mô phỏng mới và khớp các lệnh LO thỏa điều kiện';

    public function handle(PriceEngine $engine): int
    {
        $times = max(1, (int) $this->option('times'));

        for ($i = 1; $i <= $times; $i++) {
            $r = $engine->tick('artisan');
            $this->line(sprintf('[%d/%d] %s: %d mã cập nhật, %d lần khớp, %d lỗi', $i, $times, $r['status'], $r['updated'], $r['fills'], $r['errors']));

            if ($r['status'] === 'skipped') {
                $this->warn('Phiên mô phỏng đang đóng. Admin mở phiên trong trang Cấu hình.');
                break;
            }
            if ($i < $times && (int) $this->option('sleep') > 0) {
                sleep((int) $this->option('sleep'));
            }
        }

        return self::SUCCESS;
    }
}
