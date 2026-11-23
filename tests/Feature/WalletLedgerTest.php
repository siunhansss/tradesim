<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** M03: sổ dòng tiền (FR-18, quy trình 5.9b, tiêu chí mục 11 về số dư đầu kỳ, cuối kỳ). */
class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $trader;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        $this->stock = Stock::factory()->create(['reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 27_000]);
        $this->trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($this->trader);
    }

    private function order(array $data = []): void
    {
        $this->actingAs($this->trader)->post('/orders', array_merge([
            'stock_id' => $this->stock->id, 'side' => 'buy', 'type' => 'MP', 'quantity' => 100, 'request_key' => (string) Str::uuid(),
        ], $data));
    }

    public function test_period_summary_is_consistent(): void
    {
        $this->order(['quantity' => 1000]);                                 // mua 27.000.000 + phí 40.500
        $this->order(['type' => 'LO', 'limit_price' => 26_000]);            // giữ tiền
        $this->actingAs($this->trader)->delete('/orders/'.Order::query()->latest('id')->value('id'));   // giải phóng

        $balance = Wallet::query()->where('user_id', $this->trader->id)->value('balance');

        $this->actingAs($this->trader)->get('/wallet')->assertOk()
            ->assertViewHas('opening', 0)
            ->assertViewHas('inflow', 100_000_000)
            ->assertViewHas('outflow', -27_040_500)
            ->assertViewHas('closing', (int) $balance)
            ->assertViewHas('balanced', true)
            ->assertViewHas('transactions', fn ($p) => $p->total() === 7)   // init, hold, release, buy, fee, hold, release
            ->assertSee('Khớp');
    }

    public function test_future_period_has_no_rows_and_carries_balance(): void
    {
        $tomorrow = now('Asia/Ho_Chi_Minh')->addDay()->toDateString();

        $this->actingAs($this->trader)->get("/wallet?from={$tomorrow}&to={$tomorrow}")->assertOk()
            ->assertViewHas('opening', 100_000_000)
            ->assertViewHas('closing', 100_000_000)
            ->assertViewHas('transactions', fn ($p) => $p->total() === 0)
            ->assertSee('Không có biến động nào');
    }

    public function test_type_filter_and_pagination(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->order();                                                 // mỗi lệnh MP: hold, release, buy, fee
        }

        $this->actingAs($this->trader)->get('/wallet?type=fee')
            ->assertViewHas('transactions', fn ($p) => $p->total() === 6);
        $this->actingAs($this->trader)->get('/wallet?page=2')
            ->assertViewHas('transactions', fn ($p) => $p->total() === 25 && $p->count() === 5);
        $this->actingAs($this->trader)->get('/wallet?type=rut-tien')->assertSessionHasErrors('type');
    }

    public function test_only_own_wallet_and_traders(): void
    {
        $other = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($other);
        $this->order();

        $this->actingAs($other)->get('/wallet')
            ->assertViewHas('transactions', fn ($p) => $p->total() === 1);   // chỉ bút toán vốn của chính mình

        $this->app['auth']->forgetGuards();                                  // trở thành khách
        $this->get('/wallet')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get('/wallet')->assertForbidden();
    }
}
