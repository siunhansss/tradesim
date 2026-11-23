<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\JobRun;
use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SessionCloser;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Chốt phiên cuối ngày (quy trình 5.6, issue #12). */
class CloseSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $trader;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        $this->stock = Stock::factory()->create(['symbol' => 'HPG', 'reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 27_000, 'volume' => 5000]);
        $this->trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($this->trader);
    }

    public function test_close_cancels_open_orders_and_resets_reference_price(): void
    {
        $this->actingAs($this->trader)->post('/orders', [
            'stock_id' => $this->stock->id, 'side' => 'buy', 'type' => 'LO', 'quantity' => 1000,
            'limit_price' => 26_000, 'request_key' => (string) Str::uuid(),
        ]);
        $this->stock->update(['last_price' => 28_000]);   // giá cuối phiên

        $result = app(SessionCloser::class)->close('test');

        $this->assertSame(['status' => 'ok', 'cancelled' => 1, 'stocks' => 1], $result);
        $this->assertSame(OrderStatus::Cancelled, Order::query()->sole()->status);
        $this->assertSame(0, Wallet::query()->where('user_id', $this->trader->id)->sole()->reserved);

        $stock = $this->stock->fresh();
        $this->assertSame(28_000, $stock->reference_price);
        $this->assertSame(Stock::priceBand(28_000), ['ceiling_price' => $stock->ceiling_price, 'floor_price' => $stock->floor_price]);
        $this->assertSame(0, $stock->volume);
        $this->assertDatabaseHas('stock_prices', ['stock_id' => $stock->id, 'price' => 28_000, 'source' => SessionCloser::CLOSE_SOURCE]);
        $this->assertSame('ok', JobRun::query()->where('job', 'tradesim:close-session')->sole()->status);
    }

    public function test_running_twice_is_safe(): void
    {
        app(SessionCloser::class)->close('test');
        $this->assertSame(0, app(SessionCloser::class)->close('test')['cancelled']);
        $this->assertSame(27_000, $this->stock->fresh()->reference_price);
    }

    public function test_command_and_admin_button(): void
    {
        $this->artisan('tradesim:close-session')->assertSuccessful();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->from('/admin/settings')->post('/admin/session/close')
            ->assertRedirect('/admin/settings')->assertSessionHas('success');
        $this->assertDatabaseHas('audit_logs', ['action' => 'session.close', 'user_id' => $admin->id]);

        $this->actingAs($this->trader)->post('/admin/session/close')->assertForbidden();
    }
}
