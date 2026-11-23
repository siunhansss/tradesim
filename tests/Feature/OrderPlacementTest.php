<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Holding;
use App\Models\MarketSetting;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * M04: đặt lệnh MP/LO, giữ tiền và cổ phiếu, khớp lệnh MP.
 * Tiêu chí nghiệm thu mục 11; FR-06, FR-07, FR-08, FR-10; BR-02, BR-04, BR-05, BR-07, BR-08.
 */
class OrderPlacementTest extends TestCase
{
    use RefreshDatabase;

    private User $trader;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);

        // Giá tham chiếu 27.000 -> biên độ 25.150 – 28.850, bước giá 50.
        $this->stock = Stock::factory()->create([
            'symbol' => 'HPG', 'reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 26_000,
        ]);
        $this->trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($this->trader);
    }

    private function order(array $data, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->trader)->post('/orders', array_merge([
            'stock_id' => $this->stock->id,
            'side' => 'buy',
            'type' => 'MP',
            'quantity' => 1000,
            'request_key' => (string) Str::uuid(),
        ], $data));
    }

    private function setPrice(int $price): void
    {
        $this->stock->update(['last_price' => $price]);
    }

    private function wallet(): Wallet
    {
        return Wallet::query()->where('user_id', $this->trader->id)->firstOrFail();
    }

    private function holding(): ?Holding
    {
        return Holding::query()->where('user_id', $this->trader->id)->where('stock_id', $this->stock->id)->first();
    }

    /** Số dư phải tái lập được từ sổ cái; tiền giữ khớp các bút toán giữ/giải phóng (BR-14). */
    private function assertLedgerConsistent(): void
    {
        $wallet = $this->wallet();
        $rows = WalletTransaction::query()->where('wallet_id', $wallet->id)->get();
        $balance = $rows->whereNotIn('type.value', ['hold', 'release'])->sum('amount');
        $reserved = -$rows->whereIn('type.value', ['hold', 'release'])->sum('amount');

        $this->assertSame($wallet->balance, (int) $balance, 'Số dư khác tổng sổ cái');
        $this->assertSame($wallet->reserved, (int) $reserved, 'Tiền giữ khác tổng bút toán giữ');
        $this->assertGreaterThanOrEqual(0, $wallet->available());
    }

    public function test_market_buy_fills_and_updates_wallet_holding_and_history(): void
    {
        $this->order([])->assertRedirect(route('market.show', 'HPG'))->assertSessionHas('success');

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Filled, $order->status);
        $this->assertSame(1000, $order->filled_quantity);

        $trade = Trade::query()->sole();
        $this->assertSame(26_000, $trade->price);
        $this->assertSame(39_000, $trade->fee);

        $wallet = $this->wallet();
        $this->assertSame(100_000_000 - 26_039_000, $wallet->balance);
        $this->assertSame(0, $wallet->reserved);
        $this->assertSame(1000, $this->holding()->quantity);
        $this->assertSame(26_039_000, $this->holding()->total_cost);
        $this->assertSame(1000, $this->stock->fresh()->volume);
        $this->assertDatabaseHas('audit_logs', ['action' => 'order.place', 'auditable_id' => $order->id, 'result' => 'ok']);
        $this->assertLedgerConsistent();
    }

    public function test_section_6_3_example_end_to_end(): void
    {
        $this->order([]);                                   // mua 1.000 giá 26.000
        $this->setPrice(27_000);
        $this->order([]);                                   // mua 1.000 giá 27.000
        $this->setPrice(28_000);
        $this->order(['side' => 'sell', 'quantity' => 500]); // bán 500 giá 28.000

        $holding = $this->holding();
        $this->assertSame(1500, $holding->quantity);
        $this->assertSame(39_809_625, $holding->total_cost);
        $this->assertSame(695_125, $holding->realized_pnl);
        $this->assertSame(60_885_500, $this->wallet()->balance);

        $sell = Trade::query()->where('side', 'sell')->sole();
        $this->assertSame(21_000, $sell->fee);
        $this->assertSame(14_000, $sell->tax);
        $this->assertSame(695_125, $sell->realized_pnl);
        $this->assertLedgerConsistent();
    }

    public function test_buy_over_available_balance_is_rejected_without_changes(): void
    {
        // 4.000 x 26.000 = 104 triệu > 100 triệu
        $this->order(['quantity' => 4000])->assertSessionHas('error');

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Rejected, $order->status);
        $this->assertStringContainsString('Số dư khả dụng không đủ', $order->reject_reason);
        $this->assertSame(100_000_000, $this->wallet()->balance);
        $this->assertSame(0, $this->wallet()->reserved);
        $this->assertSame(0, Trade::query()->count());
        $this->assertNull($this->holding());
        $this->assertLedgerConsistent();
    }

    public function test_sell_more_than_owned_is_rejected(): void
    {
        $this->order(['side' => 'sell', 'quantity' => 100])->assertSessionHas('error');
        $this->assertSame(OrderStatus::Rejected, Order::query()->sole()->status);

        $this->order(['quantity' => 500]);
        $this->order(['side' => 'sell', 'quantity' => 600])->assertSessionHas('error');
        $this->assertSame(500, $this->holding()->quantity);
    }

    public function test_limit_buy_holds_cash_and_held_cash_cannot_be_reused(): void
    {
        $this->order(['type' => 'LO', 'limit_price' => 25_500])->assertSessionHas('success');

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(25_538_250, $order->reserved_amount);   // 25.500.000 + phí 38.250
        $wallet = $this->wallet();
        $this->assertSame(100_000_000, $wallet->balance);
        $this->assertSame(25_538_250, $wallet->reserved);
        $this->assertSame(0, Trade::query()->count());

        // Còn khả dụng 74.461.750; lệnh 3.000 x 25.600 = 76,8 triệu phải bị từ chối (BR-05).
        $this->order(['type' => 'LO', 'limit_price' => 25_600, 'quantity' => 3000])->assertSessionHas('error');
        $this->assertSame(25_538_250, $this->wallet()->reserved);
        $this->assertLedgerConsistent();
    }

    public function test_limit_sell_holds_shares(): void
    {
        $this->order(['quantity' => 1000]);
        $this->order(['side' => 'sell', 'type' => 'LO', 'limit_price' => 28_000, 'quantity' => 600])->assertSessionHas('success');

        $this->assertSame(600, $this->holding()->reserved_quantity);
        $this->assertSame(400, $this->holding()->availableQuantity());

        // 600 cp đang giữ cho lệnh bán khác, chỉ còn 400 cp khả dụng.
        $this->order(['side' => 'sell', 'quantity' => 500])->assertSessionHas('error');
        $this->order(['side' => 'sell', 'quantity' => 400])->assertSessionHas('success');
        $this->assertSame(600, $this->holding()->quantity);
        $this->assertSame(600, $this->holding()->reserved_quantity);
    }

    public function test_resubmitting_same_request_does_not_create_duplicate(): void
    {
        $key = (string) Str::uuid();
        $this->order(['request_key' => $key])->assertSessionHas('success');
        $this->order(['request_key' => $key])->assertSessionHas('warning');

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Trade::query()->count());
        $this->assertSame(1000, $this->holding()->quantity);
        $this->assertLedgerConsistent();
    }

    public function test_closed_market_and_halted_stock_are_rejected(): void
    {
        MarketSetting::put('market_open', '0');
        $this->order([])->assertSessionHas('error');
        $this->assertSame('Thị trường mô phỏng đang đóng cửa.', Order::query()->latest('id')->first()->reject_reason);

        MarketSetting::put('market_open', '1');
        $this->stock->update(['status' => 'halted']);
        $this->order([])->assertSessionHas('error');
        $this->assertSame('Mã HPG đang ngừng giao dịch.', Order::query()->latest('id')->first()->reject_reason);

        $this->assertSame(0, Trade::query()->count());
        $this->assertSame(100_000_000, $this->wallet()->balance);
    }

    public function test_invalid_input_is_rejected_by_validation(): void
    {
        $this->order(['quantity' => 150])->assertSessionHasErrors('quantity');
        $this->order(['quantity' => 0])->assertSessionHasErrors('quantity');
        $this->order(['quantity' => 'abc'])->assertSessionHasErrors('quantity');
        $this->order(['type' => 'LO'])->assertSessionHasErrors('limit_price');
        $this->order(['type' => 'LO', 'limit_price' => 30_000])->assertSessionHasErrors('limit_price');
        $this->order(['type' => 'LO', 'limit_price' => 25_510])->assertSessionHasErrors('limit_price');
        $this->order(['side' => 'hold'])->assertSessionHasErrors('side');
        $this->order(['stock_id' => 999_999])->assertSessionHasErrors('stock_id');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_only_active_traders_can_place_orders(): void
    {
        $this->post('/orders', [])->assertRedirect(route('login'));
        $this->order([], User::factory()->admin()->create())->assertForbidden();
        $this->order([], User::factory()->locked()->create())->assertForbidden();
        $this->assertSame(0, Order::query()->count());
    }

    public function test_stock_page_shows_order_ticket_for_trader_only(): void
    {
        $this->actingAs($this->trader)->get('/market/HPG')->assertOk()->assertSee('Đặt lệnh')->assertSee('name="request_key"', false);
        $this->actingAs(User::factory()->admin()->create())->get('/market/HPG')->assertOk()->assertDontSee('name="request_key"', false);
    }
}
