<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** M10: trang nhật ký kiểm toán – lọc, phân trang, phân quyền (FR-14). */
class AuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_filters_and_paginates_logs(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'qt@test.vn']);
        $trader = User::factory()->create(['email' => 'nhadautu@test.vn']);
        $audit = app(AuditLogger::class);
        for ($i = 0; $i < 22; $i++) {
            $audit->log('order.place', null, 'ok', ['i' => $i], $trader);
        }
        $audit->log('stock.delete', null, 'denied', ['reason' => 'Đã có lịch sử'], $admin);

        $get = fn (string $qs) => $this->actingAs($admin)->get('/admin/audit-logs?'.$qs)->assertOk();

        $get('')->assertViewHas('logs', fn ($p) => $p->total() === 23 && $p->count() === 20);
        $get('page=2')->assertViewHas('logs', fn ($p) => $p->count() === 3);
        $get('action=stock.delete')->assertViewHas('logs', fn ($p) => $p->total() === 1)->assertSee('Đã có lịch sử');
        $get('result=denied')->assertViewHas('logs', fn ($p) => $p->total() === 1);
        $get('q=nhadautu')->assertViewHas('logs', fn ($p) => $p->total() === 22);
        $this->actingAs($admin)->get('/admin/audit-logs?action=khong-ton-tai')->assertSessionHasErrors('action');
    }

    public function test_trader_cannot_view_logs(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/audit-logs')->assertForbidden();
    }
}
