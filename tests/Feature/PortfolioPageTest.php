<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\User;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** M05: trang danh mục (FR-11), sắp xếp, phân quyền. */
class PortfolioPageTest extends TestCase
{
    use RefreshDatabase;

    private User $trader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        $this->trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($this->trader);
    }

    private function buy(Stock $stock, int $quantity, string $side = 'buy'): void
    {
        $this->actingAs($this->trader)->post('/orders', [
            'stock_id' => $stock->id, 'side' => $side, 'type' => 'MP', 'quantity' => $quantity, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHas('success');
    }

    private function stock(string $symbol, int $price): Stock
    {
        return Stock::factory()->create(['symbol' => $symbol, 'reference_price' => $price, ...Stock::priceBand($price), 'last_price' => $price]);
    }

    public function test_page_requires_active_trader(): void
    {
        $this->get('/portfolio')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get('/portfolio')->assertForbidden();
    }

    public function test_empty_portfolio_shows_hint(): void
    {
        $this->actingAs($this->trader)->get('/portfolio')->assertOk()->assertSee('Chưa nắm giữ cổ phiếu nào');
    }

    public function test_open_and_closed_positions_are_listed(): void
    {
        $fpt = $this->stock('FPT', 118_500);
        $hpg = $this->stock('HPG', 26_000);
        $vnm = $this->stock('VNM', 61_800);
        $this->buy($fpt, 100);
        $this->buy($hpg, 1000);
        $this->buy($vnm, 200);
        $this->buy($vnm, 200, 'sell');

        $response = $this->actingAs($this->trader)->get('/portfolio')->assertOk();
        $response->assertSee('FPT')->assertSee('HPG')->assertSee('Vị thế đã bán hết');

        // Mặc định sắp xếp theo giá trị giảm dần: HPG 26 triệu đứng trước FPT 11,85 triệu.
        $response->assertViewHas('open', fn ($open) => $open->pluck('symbol')->all() === ['HPG', 'FPT']);
        $response->assertViewHas('closed', fn ($closed) => $closed->pluck('symbol')->all() === ['VNM']);
        $response->assertViewHas('allocation', fn ($a) => $a->pluck('label')->all() === ['HPG', 'FPT', 'Tiền mặt']);
    }

    public function test_sorting_by_symbol(): void
    {
        $this->buy($this->stock('HPG', 26_000), 1000);
        $this->buy($this->stock('FPT', 118_500), 100);

        $this->actingAs($this->trader)->get('/portfolio?sort=symbol&dir=asc')
            ->assertViewHas('open', fn ($open) => $open->pluck('symbol')->all() === ['FPT', 'HPG']);
        $this->actingAs($this->trader)->get('/portfolio?sort=khong-hop-le')->assertSessionHasErrors('sort');
    }
}
