<?php

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Holding;
use App\Models\JobRun;
use App\Models\Order;
use App\Models\Trade;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Format;

/**
 * Đối soát dữ liệu (mục 8.1, BR-14, BR-15, tiêu chí mục 11).
 * So sánh số dư với sổ cái, tiền/cổ phiếu giữ với lệnh mở, nắm giữ với giao dịch khớp,
 * và kiểm tra lãi đã + chưa thực hiện = tổng tài sản − vốn đầu tư ròng cho từng tài khoản.
 */
class ReconciliationService
{
    private const RESERVED_ONLY = ['hold', 'release'];

    public function __construct(private PortfolioService $portfolio) {}

    /**
     * @return array{ok: bool, wallets: int, holdings: int, issues: list<string>}
     */
    public function run(string $actor = 'scheduler'): array
    {
        $run = JobRun::query()->create(['job' => 'tradesim:reconcile', 'status' => 'running', 'details' => ['actor' => $actor], 'started_at' => now()]);
        $issues = [];

        $wallets = Wallet::query()->with('user')->get();
        foreach ($wallets as $wallet) {
            $who = $wallet->user->email;
            $rows = WalletTransaction::query()->where('wallet_id', $wallet->id);

            $ledgerBalance = (int) (clone $rows)->whereNotIn('type', self::RESERVED_ONLY)->sum('amount');
            if ($ledgerBalance !== $wallet->balance) {
                $issues[] = "{$who}: số dư ".Format::money($wallet->balance).' khác tổng sổ cái '.Format::money($ledgerBalance).'.';
            }

            $ledgerReserved = -(int) (clone $rows)->whereIn('type', self::RESERVED_ONLY)->sum('amount');
            $ordersReserved = (int) Order::query()->where('user_id', $wallet->user_id)->where('side', OrderSide::Buy->value)
                ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::PartiallyFilled->value])->sum('reserved_amount');
            if ($ledgerReserved !== $wallet->reserved || $ordersReserved !== $wallet->reserved) {
                $issues[] = "{$who}: tiền giữ ".Format::money($wallet->reserved).' khác sổ cái '.Format::money($ledgerReserved).' hoặc tổng lệnh mua mở '.Format::money($ordersReserved).'.';
            }
            if ($wallet->balance < $wallet->reserved) {
                $issues[] = "{$who}: số dư khả dụng âm (BR-02).";
            }

            // BR-15: lãi đã + chưa thực hiện = tổng tài sản − vốn đầu tư ròng.
            $s = $this->portfolio->summary($wallet->user);
            if ($s['realized'] + $s['unrealized'] !== $s['total_assets'] - $s['net_invested']) {
                $issues[] = "{$who}: vi phạm BR-15 (lãi/lỗ không khớp tổng tài sản − vốn).";
            }
        }

        $holdings = Holding::query()->with(['stock:id,symbol', 'user:id,email'])->get();
        foreach ($holdings as $h) {
            $label = "{$h->user->email} {$h->stock->symbol}";
            $trades = Trade::query()->where('user_id', $h->user_id)->where('stock_id', $h->stock_id);
            $net = (int) (clone $trades)->where('side', OrderSide::Buy->value)->sum('quantity')
                - (int) (clone $trades)->where('side', OrderSide::Sell->value)->sum('quantity');
            if ($net !== $h->quantity) {
                $issues[] = "{$label}: nắm giữ {$h->quantity} cp khác tổng giao dịch khớp {$net} cp.";
            }

            $openSell = (int) Order::query()->where('user_id', $h->user_id)->where('stock_id', $h->stock_id)
                ->where('side', OrderSide::Sell->value)
                ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::PartiallyFilled->value])->sum('reserved_quantity');
            if ($openSell !== $h->reserved_quantity) {
                $issues[] = "{$label}: cổ phiếu giữ {$h->reserved_quantity} khác tổng lệnh bán mở {$openSell}.";
            }
            if ($h->reserved_quantity > $h->quantity) {
                $issues[] = "{$label}: cổ phiếu khả dụng âm (BR-02).";
            }
            if ($h->quantity === 0 && $h->total_cost !== 0) {
                $issues[] = "{$label}: đã bán hết nhưng giá vốn còn ".Format::money($h->total_cost).'.';
            }
        }

        $result = ['ok' => $issues === [], 'wallets' => $wallets->count(), 'holdings' => $holdings->count(), 'issues' => $issues];
        $run->update(['status' => $result['ok'] ? 'ok' : 'failed', 'finished_at' => now(), 'details' => ['actor' => $actor, ...$result]]);

        return $result;
    }
}
