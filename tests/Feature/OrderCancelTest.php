<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Holding;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LimitOrderMatcher;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** M04: hủy lệnh (FR-09, quy trình 5.5, BR-06, tiêu chí mục 11). */
class OrderCancelTest extends TestCase
{
    use RefreshDatabase;

    private User $trader;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        $this->stock = Stock::factory()->create(['symbol' => 'HPG', 'reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 27_000]);
        $this->trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($this->trader);
    }

    private function place(array $data, ?User $user = null): Order
    {
        $this->actingAs($user ?? $this->trader)->post('/orders', array_merge([
            'stock_id' => $this->stock->id, 'side' => 'buy', 'type' => 'LO',
            'quantity' => 1000, 'limit_price' => 26_500, 'request_key' => (string) Str::uuid(),
        ], $data))->assertSessionHas('success');

        return Order::query()->latest('id')->firstOrFail();
    }

    private function wallet(): Wallet
    {
        return Wallet::query()->where('user_id', $this->trader->id)->sole();
    }

    public function test_cancel_pending_buy_releases_all_held_cash(): void
    {
        $order = $this->place([]);
        $this->assertSame(26_539_750, $this->wallet()->reserved);

        $this->actingAs($this->trader)->delete("/orders/{$order->id}")->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame(0, $order->reserved_amount);
        $this->assertSame(0, $this->wallet()->reserved);
        $this->assertSame(100_000_000, $this->wallet()->balance);
        $this->assertDatabaseHas('wallet_transactions', ['type' => 'release', 'amount' => 26_539_750, 'reference_id' => $order->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'order.cancel', 'auditable_id' => $order->id]);
    }

    public function test_cancel_partially_filled_keeps_filled_part(): void
    {
        $order = $this->place(['quantity' => 1500]);
        $this->stock->update(['last_price' => 26_400]);
        app(LimitOrderMatcher::class)->run();                     // khớp 1.000 cp
        $this->assertSame(OrderStatus::PartiallyFilled, $order->fresh()->status);

        $this->actingAs($this->trader)->delete("/orders/{$order->id}")
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Giữ nguyên 1.000 cp'));

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame(1000, $order->filled_quantity);         // phần đã khớp vẫn còn
        $this->assertSame(1, Trade::query()->count());           // giao dịch không bị xóa
        $this->assertSame(1000, Holding::query()->sole()->quantity);
        $this->assertSame(0, $this->wallet()->reserved);
        // Chỉ trừ tiền cho 1.000 cp đã khớp: 26.400.000 + phí 39.600
        $this->assertSame(100_000_000 - 26_439_600, $this->wallet()->balance);
    }

    public function test_cancel_sell_order_releases_held_shares(): void
    {
        $this->place(['type' => 'MP']);                            // mua 1.000 cp
        $sell = $this->place(['side' => 'sell', 'limit_price' => 28_000, 'quantity' => 600]);
        $this->assertSame(600, Holding::query()->sole()->reserved_quantity);

        $this->actingAs($this->trader)->delete("/orders/{$sell->id}")->assertSessionHas('success');

        $this->assertSame(0, Holding::query()->sole()->reserved_quantity);
        $this->assertSame(1000, Holding::query()->sole()->availableQuantity());
    }

    public function test_cannot_cancel_others_or_closed_orders(): void
    {
        $order = $this->place([]);
        $other = User::factory()->create();

        $this->actingAs($other)->delete("/orders/{$order->id}")->assertForbidden();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        $filled = $this->place(['type' => 'MP']);
        $this->actingAs($this->trader)->delete("/orders/{$filled->id}")->assertForbidden();

        $this->actingAs($this->trader)->delete("/orders/{$order->id}");
        $this->actingAs($this->trader)->delete("/orders/{$order->id}")->assertForbidden();   // hủy lần hai

        $this->delete('/orders/999999')->assertNotFound();
    }
}
