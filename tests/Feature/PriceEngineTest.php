<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Holding;
use App\Models\JobRun;
use App\Models\MarketSetting;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LimitOrderMatcher;
use App\Services\PriceEngine;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

/** M02, M04: Price Engine, lịch chạy, khớp lệnh LO (quy trình 5.6, FR-08, BR-12). */
class PriceEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $trader;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        // Cố định hạt giống để test lặp lại được.
        $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(42)));

        $this->stock = Stock::factory()->create([
            'symbol' => 'HPG', 'reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 27_000,
        ]);
        $this->trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($this->trader);
    }

    private function order(array $data): void
    {
        $this->actingAs($this->trader)->post('/orders', array_merge([
            'stock_id' => $this->stock->id, 'side' => 'buy', 'type' => 'LO',
            'quantity' => 1000, 'limit_price' => 26_500, 'request_key' => (string) Str::uuid(),
        ], $data))->assertSessionHas('success');
    }

    private function setPrice(int $price): void
    {
        $this->stock->update(['last_price' => $price]);
    }

    public function test_tick_updates_prices_within_band_and_records_history(): void
    {
        $halted = Stock::factory()->halted()->create(['last_price' => 50_000]);

        for ($i = 0; $i < 30; $i++) {
            $result = app(PriceEngine::class)->tick('test');
            $this->assertSame('ok', $result['status']);
            $stock = $this->stock->fresh();
            $this->assertGreaterThanOrEqual($stock->floor_price, $stock->last_price);
            $this->assertLessThanOrEqual($stock->ceiling_price, $stock->last_price);
            $this->assertSame(0, $stock->last_price % Stock::tickSize($stock->last_price));
        }

        $this->assertSame(PriceEngine::SOURCE, $this->stock->fresh()->price_source);
        $this->assertSame(30, $this->stock->prices()->count());
        $this->assertSame(50_000, $halted->fresh()->last_price);     // mã ngừng giao dịch không đổi giá
        $this->assertSame(0, $halted->prices()->count());
        $this->assertSame(30, JobRun::query()->where('job', 'tradesim:prices')->where('status', 'ok')->count());
    }

    public function test_tick_is_skipped_when_market_closed(): void
    {
        MarketSetting::put('market_open', '0');

        $this->assertSame('skipped', app(PriceEngine::class)->tick('test')['status']);
        $this->assertSame(27_000, $this->stock->fresh()->last_price);
        $this->assertSame('skipped', JobRun::query()->sole()->status);
    }

    public function test_limit_buy_fills_partially_then_fully_when_price_reaches_limit(): void
    {
        $this->order(['quantity' => 1500, 'limit_price' => 26_500]);
        $order = Order::query()->sole();
        $matcher = app(LimitOrderMatcher::class);

        // Giá 27.000 > giá đặt: chưa khớp.
        $this->assertSame(0, $matcher->run()['fills']);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        // Giá 26.400 ≤ giá đặt: khớp tối đa 1.000 cp ở giá 26.400.
        $this->setPrice(26_400);
        $this->assertSame(1, $matcher->run()['fills']);
        $order->refresh();
        $this->assertSame(OrderStatus::PartiallyFilled, $order->status);
        $this->assertSame(1000, $order->filled_quantity);
        $this->assertSame(26_400, Trade::query()->sole()->price);

        // Lần sau khớp nốt 500 cp; tiền giữ về 0, phần giữ thừa được trả lại.
        $this->assertSame(1, $matcher->run()['fills']);
        $order->refresh();
        $this->assertSame(OrderStatus::Filled, $order->status);
        $this->assertSame(0, $order->reserved_amount);

        $wallet = Wallet::query()->where('user_id', $this->trader->id)->sole();
        $this->assertSame(0, $wallet->reserved);
        // 1.500 x 26.400 = 39.600.000; phí 26.400.000 x 0,15% = 39.600 và 13.200.000 x 0,15% = 19.800
        $this->assertSame(100_000_000 - 39_600_000 - 39_600 - 19_800, $wallet->balance);
        $this->assertSame(1500, Holding::query()->sole()->quantity);
    }

    public function test_limit_sell_fills_when_price_reaches_limit(): void
    {
        $this->order(['type' => 'MP', 'quantity' => 1000]);                       // mua 1.000 cp giá 27.000
        $this->order(['side' => 'sell', 'quantity' => 1000, 'limit_price' => 27_500]);

        $this->assertSame(0, app(LimitOrderMatcher::class)->run()['fills']);
        $this->setPrice(27_600);
        $this->assertSame(1, app(LimitOrderMatcher::class)->run()['fills']);

        $sell = Order::query()->where('side', 'sell')->sole();
        $this->assertSame(OrderStatus::Filled, $sell->status);
        $holding = Holding::query()->sole();
        $this->assertSame(0, $holding->quantity);
        $this->assertSame(0, $holding->reserved_quantity);
        $this->assertSame(0, $holding->total_cost);
    }

    public function test_halted_stock_orders_are_not_matched(): void
    {
        $this->order(['limit_price' => 26_500]);
        $this->stock->update(['last_price' => 26_000, 'status' => 'halted']);

        $this->assertSame(0, app(LimitOrderMatcher::class)->run()['fills']);
        $this->assertSame(OrderStatus::Pending, Order::query()->sole()->status);
    }

    public function test_artisan_command_runs(): void
    {
        $this->artisan('tradesim:prices', ['--times' => 2])->assertSuccessful();
        $this->assertSame(2, JobRun::query()->count());
    }
}
