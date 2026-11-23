<?php

namespace App\Http\Controllers;

use App\Enums\LedgerType;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Sổ dòng tiền (FR-18, quy trình 5.9b): lọc theo kỳ và loại, số dư đầu kỳ, cuối kỳ, phân trang.
 * Kiểm tra: số dư đầu kỳ + tiền vào + tiền ra = số dư cuối kỳ.
 */
class WalletController extends Controller
{
    public const PER_PAGE = 20;

    private const RESERVED_ONLY = ['hold', 'release'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'type' => ['nullable', Rule::enum(LedgerType::class)],
        ]);
        $tz = config('tradesim.display_timezone');
        $from = Carbon::parse($filters['from'] ?? now($tz)->startOfMonth()->toDateString(), $tz)->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now($tz)->toDateString(), $tz)->endOfDay();
        $fromUtc = $from->copy()->utc();
        $toUtc = $to->copy()->utc();

        $wallet = $request->user()->wallet()->firstOrFail();
        $base = WalletTransaction::query()->where('wallet_id', $wallet->id);

        // Số dư sau bút toán cuối cùng trước kỳ / trong kỳ (cột balance_after).
        $opening = (int) ((clone $base)->where('created_at', '<', $fromUtc)->orderByDesc('id')->value('balance_after') ?? 0);
        $closing = (int) ((clone $base)->where('created_at', '<=', $toUtc)->orderByDesc('id')->value('balance_after') ?? $opening);

        $inRange = (clone $base)->whereBetween('created_at', [$fromUtc, $toUtc]);
        $affecting = (clone $inRange)->whereNotIn('type', self::RESERVED_ONLY);
        $inflow = (int) (clone $affecting)->where('amount', '>', 0)->sum('amount');
        $outflow = (int) (clone $affecting)->where('amount', '<', 0)->sum('amount');

        $transactions = $inRange
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('wallet.index', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'filters' => [...$filters, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'opening' => $opening,
            'inflow' => $inflow,
            'outflow' => $outflow,
            'closing' => $closing,
            'balanced' => $opening + $inflow + $outflow === $closing,
            'orderMorph' => (new Order)->getMorphClass(),
        ]);
    }
}
