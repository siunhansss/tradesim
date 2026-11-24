<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\JobRun;
use App\Models\PortfolioSnapshot;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Chụp ảnh danh mục cuối ngày (quy trình 5.9c, FR-19, BR-16).
 * Mỗi tài khoản tối đa một ảnh chụp mỗi ngày; chạy lại thì cập nhật, không tạo bản ghi mới.
 */
class PortfolioSnapshotter
{
    public function __construct(private PortfolioService $portfolio) {}

    /**
     * @return array{status: string, date: string, users: int}
     */
    public function take(?Carbon $date = null, string $actor = 'scheduler'): array
    {
        $date ??= now(config('tradesim.display_timezone'));
        $day = $date->toDateString();
        $run = JobRun::query()->create(['job' => 'tradesim:snapshot', 'status' => 'running', 'details' => ['actor' => $actor, 'date' => $day], 'started_at' => now()]);

        try {
            $count = 0;
            User::query()->where('role', Role::Trader->value)->whereHas('wallet')->orderBy('id')
                ->each(function (User $user) use ($day, &$count) {
                    $this->snapshotUser($user, $day);
                    $count++;
                });

            $run->update(['status' => 'ok', 'finished_at' => now(), 'details' => ['actor' => $actor, 'date' => $day, 'users' => $count]]);

            return ['status' => 'ok', 'date' => $day, 'users' => $count];
        } catch (Throwable $e) {
            $run->update(['status' => 'failed', 'finished_at' => now(), 'error' => $e->getMessage()]);

            return ['status' => 'failed', 'date' => $day, 'users' => 0];
        }
    }

    public function snapshotUser(User $user, string $day): PortfolioSnapshot
    {
        $s = $this->portfolio->summary($user);
        // So sánh bằng Carbon (không bằng chuỗi 'Y-m-d') để khớp cách Laravel lưu cột date trên cả SQLite và MySQL.
        $date = Carbon::createFromFormat('Y-m-d', $day, 'UTC')->startOfDay();

        return DB::transaction(function () use ($user, $date, $s) {
            $snapshot = PortfolioSnapshot::query()->updateOrCreate(
                ['user_id' => $user->id, 'snapshot_date' => $date],
                [
                    'cash' => $s['cash'],
                    'stock_value' => $s['stock_value'],
                    'total_assets' => $s['total_assets'],
                    'realized_pnl' => $s['realized'],
                    'unrealized_pnl' => $s['unrealized'],
                ],
            );

            $snapshot->items()->delete();
            foreach ($s['rows'] as $row) {
                if ($row['quantity'] > 0) {
                    $snapshot->items()->create([
                        'stock_id' => $row['holding']->stock_id,
                        'quantity' => $row['quantity'],
                        'price' => $row['price'],
                        'value' => $row['value'],
                        'weight' => round($row['weight'], 4),
                    ]);
                }
            }

            return $snapshot;
        });
    }
}
