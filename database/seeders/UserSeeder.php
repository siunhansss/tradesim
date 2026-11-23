<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

/** Tài khoản demo (ghi trong README). Mật khẩu chỉ dùng cho môi trường phát triển. */
class UserSeeder extends Seeder
{
    public function run(WalletService $wallets): void
    {
        User::factory()->admin()->create([
            'name' => 'Quản trị viên',
            'email' => 'admin@tradesim.test',
            'password' => 'admin12345',
        ]);

        $traders = [
            ['Nguyễn Minh Anh', 'minhanh@tradesim.test'],
            ['Trần Quang Huy', 'quanghuy@tradesim.test'],
        ];
        foreach ($traders as [$name, $email]) {
            $user = User::factory()->create(['name' => $name, 'email' => $email, 'password' => 'trader12345']);
            $wallets->openWithInitialCapital($user);
        }

        // Thêm người dùng để thử phân trang, tìm kiếm ở trang quản trị.
        User::factory(25)->create()->each(fn (User $u) => $wallets->openWithInitialCapital($u));
        User::factory(2)->locked()->create()->each(fn (User $u) => $wallets->openWithInitialCapital($u));
    }
}
