<?php

namespace App\Console\Commands;

use App\Services\PortfolioSnapshotter;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TakeSnapshots extends Command
{
    protected $signature = 'tradesim:snapshot {--date= : Ngày chụp (Y-m-d), mặc định hôm nay theo giờ Việt Nam}';

    protected $description = 'Chụp ảnh danh mục cuối ngày cho mọi nhà đầu tư';

    public function handle(PortfolioSnapshotter $snapshotter): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'), config('tradesim.display_timezone')) : null;
        $r = $snapshotter->take($date, 'artisan');
        $this->line("{$r['status']}: chụp {$r['users']} tài khoản cho ngày {$r['date']}.");

        return $r['status'] === 'ok' ? self::SUCCESS : self::FAILURE;
    }
}
