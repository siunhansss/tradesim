<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Dữ liệu mẫu chạy được và nhất quán: số dư ví bằng tổng sổ cái (mục 8.1). */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_consistent_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(20, Stock::query()->count());
        $this->assertTrue(User::query()->where('email', 'admin@tradesim.test')->first()->isAdmin());

        Wallet::query()->get()->each(function (Wallet $wallet) {
            $ledger = (int) WalletTransaction::query()->where('wallet_id', $wallet->id)->whereNotIn('type', ['hold', 'release'])->sum('amount');
            $this->assertSame($ledger, $wallet->balance);
        });

        Stock::query()->get()->each(function (Stock $stock) {
            $this->assertGreaterThanOrEqual($stock->floor_price, $stock->last_price);
            $this->assertLessThanOrEqual($stock->ceiling_price, $stock->last_price);
            $this->assertSame(0, $stock->reference_price % Stock::tickSize($stock->reference_price));
        });
    }
}
