<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\User;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Thông báo khớp lệnh qua Event + Listener + Notification (quy trình 5.7 bước 5). */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_filled_order_creates_notification_and_page_marks_read(): void
    {
        $this->seed(MarketSettingSeeder::class);
        $stock = Stock::factory()->create(['symbol' => 'HPG', 'reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 27_000]);
        $trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($trader);

        $this->actingAs($trader)->post('/orders', [
            'stock_id' => $stock->id, 'side' => 'buy', 'type' => 'MP', 'quantity' => 100, 'request_key' => (string) Str::uuid(),
        ])->assertSessionHas('success');

        $this->assertSame(1, $trader->unreadNotifications()->count());
        $this->assertStringContainsString('HPG đã khớp toàn bộ: 100 cp giá 27.000 đ', $trader->notifications()->first()->data['message']);

        $this->actingAs($trader)->get('/market')->assertSee('Thông báo')->assertSee('badge rounded-pill', false);
        $this->actingAs($trader)->get('/notifications')->assertOk()->assertSee('Khớp lệnh');
        $this->actingAs($trader)->post('/notifications/read-all')->assertSessionHas('success');
        $this->assertSame(0, $trader->unreadNotifications()->count());
    }

    public function test_notifications_require_login(): void
    {
        $this->get('/notifications')->assertRedirect(route('login'));
    }
}
