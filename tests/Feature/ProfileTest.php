<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** M01: hồ sơ và đổi mật khẩu (FR-01). */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_requires_login(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_user_can_view_and_update_profile(): void
    {
        $user = User::factory()->create(['name' => 'Tên cũ']);

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Tên cũ');

        $this->actingAs($user)->patch('/profile', ['name' => 'Tên mới', 'email' => 'MOI@example.com'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Tên mới', $user->name);
        $this->assertSame('moi@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'profile.update', 'user_id' => $user->id]);
    }

    public function test_profile_update_validates_empty_and_duplicate_email(): void
    {
        User::factory()->create(['email' => 'trung@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->patch('/profile', ['name' => '', 'email' => 'khong-phai-email'])
            ->assertSessionHasErrors(['name', 'email']);
        $this->actingAs($user)->from('/profile')->patch('/profile', ['name' => 'An', 'email' => 'trung@example.com'])
            ->assertSessionHasErrors('email');

        // Giữ nguyên email của chính mình không bị coi là trùng.
        $this->actingAs($user)->patch('/profile', ['name' => 'An Bình', 'email' => $user->email])->assertSessionHasNoErrors();
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'matkhau123']);

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'matkhau123',
            'password' => 'matkhaumoi456',
            'password_confirmation' => 'matkhaumoi456',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('matkhaumoi456', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'profile.password', 'user_id' => $user->id]);
    }

    public function test_change_password_rejects_wrong_current_or_weak_password(): void
    {
        $user = User::factory()->create(['password' => 'matkhau123']);

        $this->actingAs($user)->from('/profile')->put('/profile/password', [
            'current_password' => 'sai-mat-khau',
            'password' => 'matkhaumoi456',
            'password_confirmation' => 'matkhaumoi456',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->actingAs($user)->from('/profile')->put('/profile/password', [
            'current_password' => 'matkhau123',
            'password' => 'ngan',
            'password_confirmation' => 'ngan',
        ])->assertSessionHasErrorsIn('password', 'password');

        $this->assertTrue(Hash::check('matkhau123', $user->fresh()->password));
    }
}
