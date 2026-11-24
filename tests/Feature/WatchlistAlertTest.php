<?php

namespace Tests\Feature;

use App\Enums\AlertStatus;
use App\Jobs\EvaluatePriceAlerts;
use App\Models\PriceAlert;
use App\Models\Stock;
use App\Models\User;
use App\Models\Watchlist;
use App\Services\PriceEngine;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** M09: watchlist, cảnh báo giá, thông báo (FR-23, FR-24, BR-19, BR-20, quy trình 5.7). */
class WatchlistAlertTest extends TestCase
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

    private function createAlert(array $data = []): void
    {
        $this->actingAs($this->trader)->post('/alerts', array_merge([
            'stock_id' => $this->stock->id, 'condition' => 'gte', 'target_price' => 27_500,
        ], $data));
    }

    public function test_watchlist_add_and_remove(): void
    {
        $this->actingAs($this->trader)->post('/watchlist', ['stock_id' => $this->stock->id])->assertSessionHas('success');
        $this->actingAs($this->trader)->post('/watchlist', ['stock_id' => $this->stock->id]);   // thêm lại không nhân đôi
        $this->assertSame(1, Watchlist::query()->count());

        $this->actingAs($this->trader)->get('/watchlist')->assertOk()->assertSee('HPG');
        $this->actingAs($this->trader)->get('/market/HPG')->assertSee('Đang theo dõi');

        $this->actingAs($this->trader)->delete("/watchlist/{$this->stock->id}")->assertSessionHas('success');
        $this->assertSame(0, Watchlist::query()->count());
    }

    public function test_alert_validation_and_limit(): void
    {
        $this->actingAs($this->trader)->from('/alerts')
            ->post('/alerts', ['stock_id' => 999, 'condition' => 'eq', 'target_price' => 0])
            ->assertSessionHasErrors(['stock_id', 'condition', 'target_price']);

        for ($i = 0; $i < PriceAlert::MAX_ACTIVE_PER_USER; $i++) {
            $this->createAlert(['target_price' => 28_000 + $i * 50]);
        }
        $this->createAlert(['target_price' => 29_500]);
        $this->assertSame(20, PriceAlert::query()->count());   // BR-19: tối đa 20 cảnh báo đang bật
    }

    public function test_alert_triggers_once_and_can_be_re_enabled(): void
    {
        $this->createAlert();
        $alert = PriceAlert::query()->sole();

        (new EvaluatePriceAlerts)->handle();
        $this->assertSame(AlertStatus::Active, $alert->fresh()->status);           // 27.000 < 27.500: chưa chạm

        $this->stock->update(['last_price' => 27_600]);
        (new EvaluatePriceAlerts)->handle();
        (new EvaluatePriceAlerts)->handle();                                         // chạy lại không gửi trùng
        $alert->refresh();
        $this->assertSame(AlertStatus::Triggered, $alert->status);
        $this->assertSame(27_600, $alert->triggered_price);
        $this->assertSame(1, $this->trader->notifications()->count());

        $message = $this->trader->notifications()->first()->data['message'];
        $this->assertStringContainsString('HPG', $message);
        $this->assertStringNotContainsString('số dư', mb_strtolower($message));    // BR-20

        // Bật lại: kích hoạt lần nữa ở lần kiểm tra sau.
        $this->actingAs($this->trader)->put("/alerts/{$alert->id}", ['condition' => 'gte', 'target_price' => 27_500, 'status' => 'active'])
            ->assertRedirect(route('alerts.index'));
        $this->assertNull($alert->fresh()->triggered_at);
        (new EvaluatePriceAlerts)->handle();
        $this->assertSame(2, $this->trader->notifications()->count());
    }

    public function test_price_engine_queues_alert_check(): void
    {
        Queue::fake();

        app(PriceEngine::class)->tick('test');

        Queue::assertPushed(EvaluatePriceAlerts::class);
    }

    public function test_only_owner_can_edit_or_delete_alert(): void
    {
        $this->createAlert();
        $alert = PriceAlert::query()->sole();
        $other = User::factory()->create();

        $this->actingAs($other)->get("/alerts/{$alert->id}/edit")->assertForbidden();
        $this->actingAs($other)->put("/alerts/{$alert->id}", ['condition' => 'lte', 'target_price' => 1, 'status' => 'disabled'])->assertForbidden();
        $this->actingAs($other)->delete("/alerts/{$alert->id}")->assertForbidden();
        $this->actingAs($other)->get('/alerts')->assertOk()->assertDontSee('27.500');

        $this->actingAs($this->trader)->delete("/alerts/{$alert->id}")->assertSessionHas('success');
        $this->assertModelMissing($alert);
    }
}
