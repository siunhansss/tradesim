<?php

namespace Tests\Feature;

use App\Models\PortfolioSnapshot;
use App\Models\Stock;
use App\Models\User;
use App\Services\PortfolioSnapshotter;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/** M06: ảnh chụp cuối ngày, trang tài chính, báo cáo tháng, CSV (FR-19, FR-20, BR-16). */
class FinanceReportTest extends TestCase
{
    use RefreshDatabase;

    private User $trader;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        $this->stock = Stock::factory()->create(['symbol' => 'HPG', 'reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 26_000]);
        $this->trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($this->trader);
    }

    private function trade(string $side, int $quantity, int $price, ?User $user = null): void
    {
        $this->stock->update(['last_price' => $price]);
        $this->actingAs($user ?? $this->trader)->post('/orders', [
            'stock_id' => $this->stock->id, 'side' => $side, 'type' => 'MP', 'quantity' => $quantity, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHas('success');
    }

    private function month(): string
    {
        return now('Asia/Ho_Chi_Minh')->format('Y-m');
    }

    public function test_snapshot_is_one_per_user_per_day_and_updates_on_rerun(): void
    {
        $this->trade('buy', 1000, 26_000);
        User::factory()->admin()->create();   // Admin không có danh mục, không được chụp

        $this->artisan('tradesim:snapshot')->assertSuccessful();
        $this->stock->update(['last_price' => 27_000]);
        $this->artisan('tradesim:snapshot')->assertSuccessful();

        $snapshot = PortfolioSnapshot::query()->sole();                        // BR-16: vẫn chỉ một bản ghi
        $this->assertSame(27_000_000, $snapshot->stock_value);               // đã cập nhật theo giá mới
        $this->assertSame(73_961_000 + 27_000_000, $snapshot->total_assets);
        $this->assertSame(1, $snapshot->items()->count());
    }

    public function test_finance_page_shows_chart_and_drawdown(): void
    {
        $tz = 'Asia/Ho_Chi_Minh';
        $this->trade('buy', 1000, 26_000);
        $snapshotter = app(PortfolioSnapshotter::class);
        foreach ([26_000, 27_500, 25_200, 26_800] as $i => $price) {
            $this->stock->update(['last_price' => $price]);
            $snapshotter->take(now($tz)->subDays(4 - $i), 'test');
        }

        $this->actingAs($this->trader)->get('/finance')->assertOk()
            ->assertViewHas('chartValues', fn ($v) => count($v) === 4)
            ->assertViewHas('mdd', fn ($mdd) => $mdd > 2.26 && $mdd < 2.27)  // (101.461.000 - 99.161.000) / 101.461.000 = 2,267%
            ->assertSee('assetChart', false);
    }

    public function test_monthly_report_and_csv_match(): void
    {
        $this->trade('buy', 1000, 26_000);
        $this->trade('buy', 1000, 27_000);
        $this->trade('sell', 500, 28_000);

        $other = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($other);
        $this->trade('buy', 100, 28_000, $other);                              // không được lẫn vào báo cáo

        $response = $this->actingAs($this->trader)->get('/finance/reports/'.$this->month())->assertOk();
        $response->assertViewHas('r', fn ($r) => $r['trade_count'] === 3
            && $r['buy_value'] === 53_000_000
            && $r['sell_value'] === 14_000_000
            && $r['fees'] === 39_000 + 40_500 + 21_000
            && $r['taxes'] === 14_000
            && $r['realized'] === 695_125
            && $r['best']['symbol'] === 'HPG');

        $csv = $this->actingAs($this->trader)->get('/finance/reports/'.$this->month().'/csv');
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $csv->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('"Lãi/lỗ đã thực hiện (đ)",695125', $content);
        $this->assertStringContainsString('"Tổng giá trị mua (đ)",53000000', $content);
        $this->assertSame(16, substr_count(trim($content), "\n") + 1);    // 11 dòng tổng hợp + 1 dòng trống + tiêu đề + 3 giao dịch
    }

    public function test_invalid_or_future_month_returns_404(): void
    {
        $future = now('Asia/Ho_Chi_Minh')->addMonths(2)->format('Y-m');

        $this->actingAs($this->trader)->get('/finance/reports/2026-13')->assertNotFound();
        $this->actingAs($this->trader)->get('/finance/reports/abc')->assertNotFound();
        $this->actingAs($this->trader)->get("/finance/reports/{$future}")->assertNotFound();
    }

    public function test_only_traders_can_view_finance(): void
    {
        $this->get('/finance')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get('/finance')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/finance/reports/'.$this->month().'/csv')->assertForbidden();
    }

    public function test_close_session_also_takes_snapshot(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/admin/session/close')->assertSessionHas('success');

        $this->assertSame(1, PortfolioSnapshot::query()->count());
        $this->assertSame(Carbon::now('Asia/Ho_Chi_Minh')->toDateString(), PortfolioSnapshot::query()->sole()->snapshot_date->toDateString());
    }
}
