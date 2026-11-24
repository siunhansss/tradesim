<?php

namespace App\Console\Commands;

use App\Services\ReconciliationService;
use Illuminate\Console\Command;

class Reconcile extends Command
{
    protected $signature = 'tradesim:reconcile';

    protected $description = 'Đối soát ví, sổ cái, lệnh, nắm giữ và BR-15 cho mọi tài khoản';

    public function handle(ReconciliationService $service): int
    {
        $r = $service->run('artisan');
        $this->line("Đã kiểm tra {$r['wallets']} ví, {$r['holdings']} dòng nắm giữ.");

        if ($r['ok']) {
            $this->info('Khớp toàn bộ, không có chênh lệch.');

            return self::SUCCESS;
        }
        foreach ($r['issues'] as $issue) {
            $this->error($issue);
        }

        return self::FAILURE;
    }
}
