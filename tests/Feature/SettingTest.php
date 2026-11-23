<?php

namespace Tests\Feature;

use App\Models\JobRun;
use App\Models\MarketSetting;
use App\Models\Stock;
use App\Models\Trade;
use App\Models\User;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** M10: cấu hình thị trường (FR-13, FR-14, BR-04, BR-13). */
class SettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'market_open' => '1',
            'fee_percent' => '0,15',
            'tax_percent' => '0,1',
            'initial_capital' => 100_000_000,
            'max_fill_per_tick' => 1000,
            'volatility_percent' => '1,1',
        ], $overrides);
    }

    public function test_only_admin_can_access_settings(): void
    {
        $trader = User::factory()->create();

        $this->actingAs($trader)->get('/admin/settings')->assertForbidden();
        $this->actingAs($trader)->put('/admin/settings', $this->validData(['fee_percent' => '0,5']))->assertForbidden();
        $this->actingAs($trader)->post('/admin/prices/run')->assertForbidden();
        $this->assertSame('0.0015', MarketSetting::value('fee_rate'));
    }

    public function test_admin_sees_current_values(): void
    {
        $this->actingAs($this->admin)->get('/admin/settings')
            ->assertOk()->assertSee('Cấu hình thị trường')->assertSee('value="0,15"', false);
    }

    public function test_admin_updates_settings_and_changes_are_audited(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/settings', $this->validData(['market_open' => '0', 'fee_percent' => '0,2', 'initial_capital' => 50_000_000]))
            ->assertRedirect(route('admin.settings.edit'))->assertSessionHas('success');

        $this->assertFalse(MarketSetting::isMarketOpen());
        $this->assertSame('0.002', MarketSetting::value('fee_rate'));
        $this->assertSame(50_000_000, MarketSetting::initialCapital());
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.update', 'user_id' => $this->admin->id]);
    }

    public function test_unchecked_switch_closes_market(): void
    {
        $data = $this->validData();
        unset($data['market_open']);   // trình duyệt không gửi checkbox khi bỏ chọn

        $this->actingAs($this->admin)->put('/admin/settings', $data)->assertSessionHasNoErrors();
        $this->assertFalse(MarketSetting::isMarketOpen());
    }

    public function test_invalid_settings_are_rejected(): void
    {
        $this->actingAs($this->admin)->from('/admin/settings')
            ->put('/admin/settings', $this->validData([
                'fee_percent' => '5', 'tax_percent' => 'abc', 'initial_capital' => '', 'max_fill_per_tick' => 150, 'volatility_percent' => '10',
            ]))
            ->assertSessionHasErrors(['fee_percent', 'tax_percent', 'initial_capital', 'max_fill_per_tick', 'volatility_percent']);

        $this->assertSame('0.0015', MarketSetting::value('fee_rate'));
    }

    public function test_fee_change_does_not_alter_existing_trades(): void
    {
        $stock = Stock::factory()->create(['reference_price' => 26_000, ...Stock::priceBand(26_000), 'last_price' => 26_000]);
        $trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($trader);
        $buy = fn () => $this->actingAs($trader)->post('/orders', [
            'stock_id' => $stock->id, 'side' => 'buy', 'type' => 'MP', 'quantity' => 1000, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHas('success');

        $buy();
        $this->actingAs($this->admin)->put('/admin/settings', $this->validData(['fee_percent' => '0,2']));
        $buy();

        [$old, $new] = Trade::query()->orderBy('id')->get()->all();
        $this->assertSame(39_000, $old->fee);           // 0,15% của 26 triệu, giữ nguyên
        $this->assertSame('0.001500', $old->fee_rate);
        $this->assertSame(52_000, $new->fee);           // 0,2% của 26 triệu
        $this->assertSame('0.002000', $new->fee_rate);
    }

    public function test_admin_can_run_price_engine(): void
    {
        Stock::factory(3)->create();

        $this->actingAs($this->admin)->from('/admin/settings')->post('/admin/prices/run')
            ->assertRedirect('/admin/settings')->assertSessionHas('success');
        $this->assertSame('ok', JobRun::query()->sole()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'prices.run']);
    }
}
