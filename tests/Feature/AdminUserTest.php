<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Stock;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/** M10: quản lý người dùng – CRUD, tìm kiếm, lọc, phân trang, phân quyền (FR-02, FR-13, FR-14). */
class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
        $this->admin = User::factory()->admin()->create(['name' => 'Quản trị', 'email' => 'admin@test.vn']);
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lê Văn Mới', 'email' => 'moi@test.vn', 'password' => 'matkhau123', 'password_confirmation' => 'matkhau123',
            'role' => 'trader', 'status' => 'active',
        ], $overrides);
    }

    public function test_only_admin_can_manage_users(): void
    {
        $trader = User::factory()->create();

        $this->get('/admin/users')->assertRedirect(route('login'));
        $this->actingAs($trader)->get('/admin/users')->assertForbidden();
        $this->actingAs($trader)->post('/admin/users', $this->data())->assertForbidden();
        $this->actingAs($trader)->delete("/admin/users/{$this->admin->id}")->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'moi@test.vn']);
    }

    public function test_index_search_filter_sort_and_pagination(): void
    {
        User::factory()->create(['name' => 'Nguyễn Thu Hà', 'email' => 'thuha@test.vn']);
        User::factory()->locked()->create(['name' => 'Bị Khóa', 'email' => 'khoa@test.vn']);
        User::factory(20)->create();
        $get = fn (string $qs) => $this->actingAs($this->admin)->get('/admin/users?'.$qs)->assertOk();

        $get('q=thuha')->assertViewHas('users', fn ($p) => $p->total() === 1)->assertSee('Nguyễn Thu Hà');
        $get('status=locked')->assertViewHas('users', fn ($p) => $p->total() === 1 && $p->first()->email === 'khoa@test.vn');
        $get('role=admin')->assertViewHas('users', fn ($p) => $p->total() === 1);
        $get('page=2')->assertViewHas('users', fn ($p) => $p->total() === 23 && $p->count() === 8);
        $get('sort=email&dir=asc')->assertViewHas('users', fn ($p) => $p->first()->email <= $p->items()[1]->email);
        $this->actingAs($this->admin)->get('/admin/users?sort=password')->assertSessionHasErrors('sort');
    }

    public function test_admin_creates_trader_with_wallet(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', $this->data())
            ->assertRedirect(route('admin.users.index'))->assertSessionHas('success');

        $user = User::query()->where('email', 'moi@test.vn')->sole();
        $this->assertTrue($user->isTrader());
        $this->assertTrue(Hash::check('matkhau123', $user->password));
        $this->assertSame(100_000_000, Wallet::query()->where('user_id', $user->id)->value('balance') + 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.create', 'auditable_id' => $user->id]);

        // Không lưu mật khẩu vào nhật ký (mục 8.2).
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.create', 'details' => json_encode(['password' => 'matkhau123'])]);
    }

    public function test_admin_account_gets_no_wallet(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', $this->data(['email' => 'qt2@test.vn', 'role' => 'admin']));

        $this->assertNull(User::query()->where('email', 'qt2@test.vn')->sole()->wallet()->first());
    }

    public function test_create_validates_input(): void
    {
        User::factory()->create(['email' => 'trung@test.vn']);

        $this->actingAs($this->admin)->from('/admin/users/create')
            ->post('/admin/users', $this->data(['name' => '', 'email' => 'trung@test.vn', 'password' => 'abc', 'password_confirmation' => 'abc', 'role' => 'god']))
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
        $this->actingAs($this->admin)
            ->post('/admin/users', $this->data(['password' => '']))
            ->assertSessionHasErrors('password');
    }

    public function test_admin_updates_user_and_can_lock_or_reset_password(): void
    {
        $user = User::factory()->create(['password' => 'cu123456']);

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}", $this->data([
            'email' => $user->email, 'name' => 'Tên Mới', 'status' => 'locked', 'password' => '', 'password_confirmation' => '',
        ]))->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('Tên Mới', $user->name);
        $this->assertSame(UserStatus::Locked, $user->status);
        $this->assertTrue(Hash::check('cu123456', $user->password));        // để trống = giữ mật khẩu

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}", $this->data(['email' => $user->email, 'password' => 'moi123456', 'password_confirmation' => 'moi123456']));
        $this->assertTrue(Hash::check('moi123456', $user->fresh()->password));
    }

    public function test_admin_cannot_demote_or_lock_self(): void
    {
        $this->actingAs($this->admin)->put("/admin/users/{$this->admin->id}", $this->data([
            'email' => 'admin@test.vn', 'role' => 'trader', 'status' => 'locked', 'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasErrors(['role', 'status']);

        $this->assertSame(Role::Admin, $this->admin->fresh()->role);
        $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->id}")->assertSessionHas('error');
        $this->assertModelExists($this->admin);
    }

    public function test_delete_only_users_without_trading_history(): void
    {
        $fresh = User::factory()->create();
        $this->actingAs($this->admin)->delete("/admin/users/{$fresh->id}")->assertSessionHas('success');
        $this->assertModelMissing($fresh);

        $trader = User::factory()->create();
        app(WalletService::class)->openWithInitialCapital($trader);
        $stock = Stock::factory()->create(['reference_price' => 27_000, ...Stock::priceBand(27_000), 'last_price' => 27_000]);
        $this->actingAs($trader)->post('/orders', [
            'stock_id' => $stock->id, 'side' => 'buy', 'type' => 'MP', 'quantity' => 100, 'request_key' => (string) Str::uuid(),
        ]);

        $this->actingAs($this->admin)->delete("/admin/users/{$trader->id}")->assertSessionHas('error');
        $this->assertModelExists($trader);
    }
}
