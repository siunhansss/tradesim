<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\WalletService;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private WalletService $wallets, private AuditLogger $audit) {}

    public function create(): View
    {
        return view('auth.register');
    }

    /** Quy trình 5.1: tạo tài khoản, tạo ví và cấp vốn trong cùng một transaction. */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::query()->create([
                ...$request->only('name', 'email', 'password'),
                'role' => Role::Trader,
                'status' => UserStatus::Active,
            ]);
            $this->audit->log('auth.register', $user, 'ok', [], $user);
            $this->wallets->openWithInitialCapital($user);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $capital = Format::money($user->wallet()->value('balance'));

        return redirect()->route('dashboard')
            ->with('success', "Tạo tài khoản thành công. Bạn được cấp {$capital} đ tiền ảo để luyện tập.");
    }
}
