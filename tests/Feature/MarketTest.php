<?php

namespace Tests\Feature;

use App\Models\Stock;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** M02: bảng giá, chi tiết mã, không tìm thấy dữ liệu (FR-03, FR-04, BR-12). */
class MarketTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_market_is_visible_to_guests(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/market')->assertOk()->assertSee('ACB')->assertSee('Giá chưa được cập nhật');
        $this->get('/market/FPT')->assertOk()->assertSee('CTCP FPT')->assertSee('Nguồn');
    }

    public function test_search_sort_and_pagination(): void
    {
        Stock::factory()->create(['symbol' => 'AAA', 'name' => 'Công ty Alpha']);
        Stock::factory(20)->create();

        $this->get('/market?q=alpha')->assertOk()->assertSee('AAA');
        $this->get('/market?sort=change&dir=desc')->assertOk();
        $this->get('/market?page=2')->assertOk()->assertViewHas('stocks', fn ($p) => $p->total() === 21 && $p->currentPage() === 2);
        $this->get('/market?q=khong-co-ma-nay')->assertOk()->assertSee('Không tìm thấy mã nào phù hợp');
    }

    public function test_unknown_or_hidden_stock_returns_404(): void
    {
        $hidden = Stock::factory()->hidden()->create();

        $this->get('/market/ZZZ')->assertNotFound();
        $this->get("/market/{$hidden->symbol}")->assertNotFound();
        $this->get('/market')->assertDontSee($hidden->name);
    }
}
