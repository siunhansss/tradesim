<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobRun;
use App\Services\AuditLogger;
use App\Services\ReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Đối soát dữ liệu cho Admin (mục 8.1). */
class ReconcileController extends Controller
{
    public function index(): View
    {
        return view('admin.reconcile.index', [
            'runs' => JobRun::query()->where('job', 'tradesim:reconcile')->latest('id')->paginate(10),
        ]);
    }

    public function store(Request $request, ReconciliationService $service, AuditLogger $audit): RedirectResponse
    {
        $r = $service->run('admin:'.$request->user()->email);
        $audit->log('reconcile.run', null, $r['ok'] ? 'ok' : 'failed', ['issues' => count($r['issues'])]);

        return redirect()->route('admin.reconcile.index')->with(
            $r['ok'] ? 'success' : 'error',
            $r['ok'] ? "Đối soát khớp toàn bộ: {$r['wallets']} ví, {$r['holdings']} dòng nắm giữ." : 'Phát hiện '.count($r['issues']).' chênh lệch. Xem chi tiết bên dưới.',
        );
    }
}
