<?php

namespace Tests\Feature;

use App\Enums\LedgerType;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Database\Seeders\MarketSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** M01: đăng ký, đăng nhập, đăng xuất, cấp vốn một lần (FR-01, FR-05, BR-01). */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketSettingSeeder::class);
    }

    public function test_register_creates_trader_with_initial_capital(): void
    {
        $response = $this->post('/register', [
            'name' => 'Lê Thu Trang',
            'email' => 'Trang@Example.com',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user = User::query()->where('email', 'trang@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->isTrader());
        $this->assertSame(100_000_000, $user->wallet()->value('balance') + 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.register', 'user_id' => $user->id]);
    }

    public function test_initial_capital_is_granted_only_once(): void
    {
        $user = User::factory()->create();
        $service = app(WalletService::class);

        $service->openWithInitialCapital($user);
        $service->openWithInitialCapital($user);

        $this->assertSame(1, WalletTransaction::query()->where('type', LedgerType::Init)->count());
        $this->assertSame(100_000_000, $user->wallet()->first()->balance);
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['email' => 'dung@example.com']);

        $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'dung@example.com',
            'password' => 'abc',
            'password_confirmation' => 'xyz',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'matkhau123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'matkhau123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('market.index'));
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'matkhau123']);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'sai-mat-khau'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = User::factory()->create(['password' => 'matkhau123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'sai']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'matkhau123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_locked_user_cannot_login(): void
    {
        $user = User::factory()->locked()->create(['password' => 'matkhau123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'matkhau123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
