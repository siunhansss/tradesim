<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\User;
use App\Services\PortfolioService;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** M05, M06: định giá danh mục, lãi/lỗ, ROI (FR-11, FR-17, BR-15, ví dụ mục 6.3). */
class PortfolioServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $trader;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        $this->stock = Stock::factory()->create([
            'symbol' => 'HPG', 'reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 26_000,
        ]);
        $this->trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($this->trader);
    }

    private function trade(string $side, int $quantity, int $price): void
    {
        $this->stock->update(['last_price' => $price]);
        $this->actingAs($this->trader)->post('/orders', [
            'stock_id' => $this->stock->id, 'side' => $side, 'type' => 'MP',
            'quantity' => $quantity, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHas('success');
    }

    public function test_new_account_has_only_cash(): void
    {
        $s = app(PortfolioService::class)->summary($this->trader);

        $this->assertSame([], $s['rows']);
        $this->assertSame(100_000_000, $s['total_assets']);
        $this->assertSame(100_000_000, $s['net_invested']);
        $this->assertSame(0.0, $s['roi']);
    }

    public function test_summary_matches_section_6_3_example(): void
    {
        $this->trade('buy', 1000, 26_000);
        $this->trade('buy', 1000, 27_000);
        $this->trade('sell', 500, 28_000);
        $this->stock->update(['last_price' => 27_500]);   // giá định giá cuối ngày

        $s = app(PortfolioService::class)->summary($this->trader);

        $this->assertSame(60_885_500, $s['cash']);
        $this->assertSame(41_250_000, $s['stock_value']);
        $this->assertSame(102_135_500, $s['total_assets']);
        $this->assertSame(695_125, $s['realized']);
        $this->assertSame(1_440_375, $s['unrealized']);
        $this->assertEqualsWithDelta(2.14, $s['roi'], 0.005);
        $this->assertEqualsWithDelta(26_539.75, $s['rows'][0]['average_cost'], 0.001);
        // BR-15: lãi đã TH + chưa TH = tổng tài sản - vốn đầu tư ròng
        $this->assertSame($s['total_assets'] - $s['net_invested'], $s['realized'] + $s['unrealized']);
    }

    public function test_dashboard_shows_portfolio(): void
    {
        $this->trade('buy', 1000, 26_000);

        $this->actingAs($this->trader)->get('/dashboard')
            ->assertOk()
            ->assertSee('Tổng tài sản')
            ->assertSee('HPG')
            ->assertSee('99.961.000');   // 73.961.000 tiền + 26.000.000 cổ phiếu
    }
}
