<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('dashboard', [
                'user' => $user,
                'stats' => [
                    'Người dùng' => User::query()->count(),
                    'Mã cổ phiếu' => Stock::query()->count(),
                    'Lệnh đang chờ' => Order::query()->whereIn('status', ['pending', 'partially_filled'])->count(),
                ],
            ]);
        }

        $user->load(['wallet', 'holdings.stock']);

        return view('dashboard', [
            'user' => $user,
            'wallet' => $user->wallet,
            'holdings' => $user->holdings->where('quantity', '>', 0),
            'transactions' => $user->wallet?->transactions()->latest('id')->limit(10)->get() ?? collect(),
        ]);
    }
}
