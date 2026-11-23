<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\User;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** M04: sổ lệnh và giao dịch khớp, lọc, sắp xếp, phân trang (FR-08, FR-12). */
class OrderBookTest extends TestCase
{
    use RefreshDatabase;

    private User $trader;

    private Stock $hpg;

    private Stock $fpt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        $this->hpg = Stock::factory()->create(['symbol' => 'HPG', 'reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 27_000]);
        $this->fpt = Stock::factory()->create(['symbol' => 'FPT', 'reference_price' => 118_500, ...Stock::priceBand(118_500), 'last_price' => 118_500]);
        $this->trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($this->trader);
    }

    private function order(Stock $stock, array $data = [], ?User $user = null): void
    {
        $this->actingAs($user ?? $this->trader)->post('/orders', array_merge([
            'stock_id' => $stock->id, 'side' => 'buy', 'type' => 'MP', 'quantity' => 100, 'request_key' => (string) Str::uuid(),
        ], $data));
    }

    public function test_order_book_lists_only_own_orders(): void
    {
        $other = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($other);
        $this->order($this->fpt, [], $other);
        $this->order($this->hpg);

        $this->actingAs($this->trader)->get('/orders')->assertOk()
            ->assertViewHas('orders', fn ($p) => $p->total() === 1 && $p->first()->stock->symbol === 'HPG');
    }

    public function test_filters_and_pagination(): void
    {
        $this->order($this->hpg);                                                     // MP khớp
        $this->order($this->hpg, ['type' => 'LO', 'limit_price' => 26_000]);        // LO chờ
        $this->order($this->fpt, ['quantity' => 10_000]);                            // bị từ chối (thiếu tiền)
        for ($i = 0; $i < 15; $i++) {
            $this->order($this->hpg, ['type' => 'LO', 'limit_price' => 26_000]);     // 15 lệnh chờ, mỗi lệnh giữ ~2,6 triệu
        }

        $get = fn (string $qs) => $this->actingAs($this->trader)->get('/orders?'.$qs)->assertOk();

        $get('q=hpg')->assertViewHas('orders', fn ($p) => $p->total() === 17);
        $get('q=fpt')->assertViewHas('orders', fn ($p) => $p->total() === 1);
        $get('status=open')->assertViewHas('orders', fn ($p) => $p->total() === 16);
        $get('status=filled')->assertViewHas('orders', fn ($p) => $p->total() === 1);
        $get('status=rejected')->assertViewHas('orders', fn ($p) => $p->total() === 1)->assertSee('Số dư khả dụng không đủ');
        $get('type=MP')->assertViewHas('orders', fn ($p) => $p->total() === 2);
        $get('side=sell')->assertViewHas('orders', fn ($p) => $p->total() === 0)->assertSee('Không có lệnh nào phù hợp');
        $get('page=2')->assertViewHas('orders', fn ($p) => $p->currentPage() === 2 && $p->count() === 3);
        $get('from='.now('Asia/Ho_Chi_Minh')->toDateString())->assertViewHas('orders', fn ($p) => $p->total() === 18);
        $get('to='.now('Asia/Ho_Chi_Minh')->toDateString())->assertSessionHasNoErrors();          // chỉ nhập "đến ngày"
        // Dữ liệu sai: quay lại trang trước (302) kèm lỗi, nên không dùng $get (vốn đòi 200).
        $this->actingAs($this->trader)->get('/orders?from=2026-12-01&to=2000-01-01')
            ->assertSessionHasErrors('to');
    }

    public function test_sorting_and_invalid_parameters(): void
    {
        $this->order($this->hpg, ['quantity' => 300]);
        $this->order($this->hpg, ['quantity' => 100]);

        $this->actingAs($this->trader)->get('/orders?sort=quantity&dir=asc')
            ->assertViewHas('orders', fn ($p) => $p->pluck('quantity')->all() === [100, 300]);
        $this->actingAs($this->trader)->get('/orders?sort=password')->assertSessionHasErrors('sort');
        $this->actingAs($this->trader)->get('/orders?status=deleted')->assertSessionHasErrors('status');
    }

    public function test_trades_page_shows_totals_and_filters(): void
    {
        $this->order($this->hpg, ['quantity' => 1000]);                    // 27.000.000
        $this->order($this->fpt, ['quantity' => 100]);                     // 11.850.000
        $this->order($this->hpg, ['side' => 'sell', 'quantity' => 500]);  // 13.500.000

        $this->actingAs($this->trader)->get('/trades')->assertOk()
            ->assertViewHas('totals', fn ($t) => (int) $t->count === 3 && $t->value === 52_350_000);
        $this->actingAs($this->trader)->get('/trades?q=hpg&side=sell')
            ->assertViewHas('trades', fn ($p) => $p->total() === 1)
            ->assertViewHas('totals', fn ($t) => $t->value === 13_500_000);
    }

    public function test_pages_require_trader(): void
    {
        $this->get('/orders')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get('/orders')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/trades')->assertForbidden();
    }
}
