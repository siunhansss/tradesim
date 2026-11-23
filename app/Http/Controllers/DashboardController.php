<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use App\Services\PortfolioService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PortfolioService $portfolio): View
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

        $wallet = $user->wallet()->first();

        return view('dashboard', [
            'user' => $user,
            'summary' => $portfolio->summary($user),
            'transactions' => $wallet?->transactions()->latest('id')->limit(10)->get() ?? collect(),
        ]);
    }
}
