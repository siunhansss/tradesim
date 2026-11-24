<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Holding;
use App\Models\JobRun;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LimitOrderMatcher;
use App\Services\ReconciliationService;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Đối soát (mục 8.1, BR-15) và khớp ngay lệnh LO đủ điều kiện khi đặt. */
class ReconcileTest extends TestCase
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

    private function order(array $data): void
    {
        $this->actingAs($this->trader)->post('/orders', array_merge([
            'stock_id' => $this->stock->id, 'side' => 'buy', 'type' => 'MP', 'quantity' => 1000, 'request_key' => (string) Str::uuid(),
        ], $data))->assertSessionHas('success');
    }

    /** Tạo đủ loại nghiệp vụ: mua MP, bán MP, LO chờ, LO khớp một phần, hủy lệnh. */
    private function busyAccount(): void
    {
        $this->order([]);
        $this->order(['side' => 'sell', 'quantity' => 300]);
        $this->order(['type' => 'LO', 'limit_price' => 26_000, 'quantity' => 500]);
        $this->order(['side' => 'sell', 'type' => 'LO', 'limit_price' => 28_000, 'quantity' => 200]);
        $this->order(['type' => 'LO', 'limit_price' => 26_500, 'quantity' => 1500]);
        $this->stock->update(['last_price' => 26_400]);
        app(LimitOrderMatcher::class)->run();
        $this->actingAs($this->trader)->delete('/orders/'.Order::query()->where('status', 'partially_filled')->value('id'));
        $this->stock->update(['last_price' => 27_300]);
    }

    public function test_consistent_data_passes_reconciliation(): void
    {
        $this->busyAccount();

        $result = app(ReconciliationService::class)->run('test');

        $this->assertTrue($result['ok'], implode("\n", $result['issues']));
        $this->assertSame(1, $result['wallets']);
        $this->assertSame('ok', JobRun::query()->where('job', 'tradesim:reconcile')->sole()->status);
    }

    public function test_tampered_data_is_detected(): void
    {
        $this->busyAccount();
        // Giả lập sửa tay CSDL sai quy trình.
        Wallet::query()->where('user_id', $this->trader->id)->increment('balance', 1_000_000);
        Holding::query()->increment('quantity', 100);

        $result = app(ReconciliationService::class)->run('test');

        $this->assertFalse($result['ok']);
        $this->assertTrue(collect($result['issues'])->contains(fn ($i) => str_contains($i, 'khác tổng sổ cái')));
        $this->assertTrue(collect($result['issues'])->contains(fn ($i) => str_contains($i, 'khác tổng giao dịch khớp')));
        $this->artisan('tradesim:reconcile')->assertFailed();
    }

    public function test_admin_page_and_button(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/reconcile')->assertRedirect(route('admin.reconcile.index'))->assertSessionHas('success');
        $this->actingAs($admin)->get('/admin/reconcile')->assertOk()->assertSee('Khớp');
        $this->actingAs($this->trader)->get('/admin/reconcile')->assertForbidden();
        $this->artisan('tradesim:reconcile')->assertSuccessful();
    }

    public function test_marketable_limit_buy_fills_immediately_at_current_price(): void
    {
        // Giá hiện tại 27.000, đặt mua LO 27.500: khớp ngay ở 27.000, trả lại tiền giữ thừa.
        $this->order(['type' => 'LO', 'limit_price' => 27_500, 'quantity' => 1000]);

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Filled, $order->status);
        $this->assertSame(27_000, Trade::query()->sole()->price);
        $wallet = Wallet::query()->where('user_id', $this->trader->id)->sole();
        $this->assertSame(0, $wallet->reserved);
        $this->assertSame(100_000_000 - 27_000_000 - 40_500, $wallet->balance);
    }

    public function test_large_marketable_limit_order_fills_partially_then_waits(): void
    {
        $this->order(['type' => 'LO', 'limit_price' => 27_000, 'quantity' => 1500]);

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::PartiallyFilled, $order->status);
        $this->assertSame(1000, $order->filled_quantity);

        app(LimitOrderMatcher::class)->run();
        $this->assertSame(OrderStatus::Filled, $order->fresh()->status);
        $this->assertTrue(app(ReconciliationService::class)->run('test')['ok']);
    }
}
