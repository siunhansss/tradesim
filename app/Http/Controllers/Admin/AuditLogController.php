<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Xem nhật ký kiểm toán (FR-14): lọc theo hành động, kết quả, người thực hiện, khoảng ngày; phân trang. */
class AuditLogController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');
        $filters = $request->validate([
            'action' => ['nullable', Rule::in($actions->all())],
            'result' => ['nullable', Rule::in(['ok', 'denied', 'failed', 'skipped'])],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);
        $tz = config('tradesim.display_timezone');

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($filters['action'] ?? null, fn ($q, $a) => $q->where('action', $a))
            ->when($filters['result'] ?? null, fn ($q, $r) => $q->where('result', $r))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->whereHas('user', fn ($u) => $u
                ->where('email', 'like', '%'.$term.'%')->orWhere('name', 'like', '%'.$term.'%')))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', Carbon::parse($d, $tz)->startOfDay()->utc()))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', Carbon::parse($d, $tz)->endOfDay()->utc()))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.audit.index', ['logs' => $logs, 'filters' => $filters, 'actions' => $actions]);
    }
}
