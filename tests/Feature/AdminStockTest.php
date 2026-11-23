<?php

namespace Tests\Feature;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** M10: CRUD mã cổ phiếu, tìm kiếm, phân trang, phân quyền, kiểm tra dữ liệu (FR-02, FR-13, FR-14). */
class AdminStockTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'symbol' => 'vjc',
            'name' => 'CTCP Hàng không VietJet',
            'exchange' => 'HOSE',
            'industry' => 'Hàng không',
            'reference_price' => 102_500,
            'status' => 'active',
        ], $overrides);
    }

    public function test_trader_and_guest_cannot_access_admin(): void
    {
        $this->get('/admin/stocks')->assertRedirect(route('login'));

        $trader = User::factory()->create();
        $this->actingAs($trader)->get('/admin/stocks')->assertForbidden();
        $this->actingAs($trader)->post('/admin/stocks', $this->validData())->assertForbidden();
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_admin_can_create_stock_with_price_band(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/stocks', $this->validData())
            ->assertRedirect(route('admin.stocks.index'))
            ->assertSessionHas('success');

        $stock = Stock::query()->where('symbol', 'VJC')->firstOrFail();
        $this->assertSame(109_600, $stock->ceiling_price);
        $this->assertSame(95_400, $stock->floor_price);
        $this->assertSame(102_500, $stock->last_price);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock.create', 'auditable_id' => $stock->id]);
    }

    public function test_create_rejects_empty_wrong_format_and_duplicate(): void
    {
        Stock::factory()->create(['symbol' => 'VNM']);
        $admin = $this->admin();

        $this->actingAs($admin)->from('/admin/stocks/create')
            ->post('/admin/stocks', $this->validData(['symbol' => '', 'name' => '']))
            ->assertSessionHasErrors(['symbol', 'name']);

        $this->actingAs($admin)
            ->post('/admin/stocks', $this->validData(['symbol' => 'VN-1', 'reference_price' => 'abc']))
            ->assertSessionHasErrors(['symbol', 'reference_price']);

        $this->actingAs($admin)
            ->post('/admin/stocks', $this->validData(['symbol' => 'vnm']))
            ->assertSessionHasErrors(['symbol' => 'Mã VNM đã tồn tại.']);

        $this->actingAs($admin)
            ->post('/admin/stocks', $this->validData(['reference_price' => 102_550]))
            ->assertSessionHasErrors('reference_price');

        $this->assertDatabaseCount('stocks', 1);
    }

    public function test_admin_can_update_and_band_is_recalculated(): void
    {
        $stock = Stock::factory()->create(['symbol' => 'HPG', 'reference_price' => 26_350, ...Stock::priceBand(26_350), 'last_price' => 26_350]);

        $this->actingAs($this->admin())
            ->put("/admin/stocks/{$stock->id}", $this->validData(['symbol' => 'HPG', 'name' => 'Hòa Phát', 'reference_price' => 30_000]))
            ->assertRedirect(route('admin.stocks.index'));

        $stock->refresh();
        $this->assertSame('Hòa Phát', $stock->name);
        $this->assertSame(32_100, $stock->ceiling_price);
        $this->assertSame(27_900, $stock->floor_price);
        $this->assertSame(27_900, $stock->last_price);
    }

    public function test_stock_without_history_can_be_deleted(): void
    {
        $stock = Stock::factory()->create();

        $this->actingAs($this->admin())->delete("/admin/stocks/{$stock->id}")->assertSessionHas('success');
        $this->assertModelMissing($stock);
    }

    public function test_stock_with_orders_cannot_be_deleted(): void
    {
        $stock = Stock::factory()->create();
        $trader = User::factory()->create();
        Order::query()->create([
            'user_id' => $trader->id, 'stock_id' => $stock->id, 'side' => OrderSide::Buy, 'type' => OrderType::Limit,
            'quantity' => 100, 'limit_price' => $stock->reference_price, 'status' => OrderStatus::Pending, 'request_key' => 'T-1',
        ]);

        $this->actingAs($this->admin())->delete("/admin/stocks/{$stock->id}")->assertSessionHas('error');
        $this->assertModelExists($stock);
    }

    public function test_index_supports_search_filter_and_pagination(): void
    {
        Stock::factory()->create(['symbol' => 'FPT', 'name' => 'CTCP FPT']);
        Stock::factory()->halted()->create(['symbol' => 'SSI']);
        Stock::factory(20)->create();
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/stocks?q=fpt')->assertOk()->assertSee('CTCP FPT')->assertDontSee('SSI');
        $this->actingAs($admin)->get('/admin/stocks?status=halted')->assertOk()->assertSee('SSI')->assertDontSee('CTCP FPT');
        $this->actingAs($admin)->get('/admin/stocks?page=2')->assertOk()->assertViewHas('stocks', fn ($p) => $p->currentPage() === 2 && $p->total() === 22);
        $this->actingAs($admin)->get('/admin/stocks?sort=password')->assertSessionHasErrors('sort');
    }
}
