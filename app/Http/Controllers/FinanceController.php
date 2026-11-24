<?php

namespace App\Http\Controllers;

use App\Models\PortfolioSnapshot;
use App\Services\FinanceCalculator;
use App\Services\PortfolioService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Quản lý tài chính danh mục (M06): tổng quan, biểu đồ tổng tài sản, MDD, báo cáo tháng, CSV. */
class FinanceController extends Controller
{
    public function index(Request $request, PortfolioService $portfolio, FinanceCalculator $calc): View
    {
        $user = $request->user();
        $snapshots = PortfolioSnapshot::query()->where('user_id', $user->id)
            ->orderByDesc('snapshot_date')->limit(90)->get()->reverse()->values();
        $summary = $portfolio->summary($user);

        $tz = config('tradesim.display_timezone');
        $months = collect(range(0, 5))->map(fn ($i) => now($tz)->startOfMonth()->subMonths($i)->format('Y-m'));

        return view('finance.index', [
            'summary' => $summary,
            'snapshots' => $snapshots,
            'chartLabels' => $snapshots->map(fn ($s) => $s->snapshot_date->format('d/m'))->all(),
            'chartValues' => $snapshots->pluck('total_assets')->all(),
            'mdd' => $calc->maxDrawdown($snapshots->pluck('total_assets')->all()),
            'peak' => $snapshots->max('total_assets'),
            'months' => $months,
        ]);
    }

    public function report(Request $request, string $month, ReportService $reports): View
    {
        $this->validateMonth($month);

        return view('finance.report', ['r' => $reports->monthly($request->user(), $month)]);
    }

    /** Xuất CSV (UTF-8 có BOM để Excel đọc đúng tiếng Việt). Số liệu trùng với trang báo cáo. */
    public function csv(Request $request, string $month, ReportService $reports): StreamedResponse
    {
        $this->validateMonth($month);
        $rows = $reports->csvRows($reports->monthly($request->user(), $month));

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, "tradesim-bao-cao-{$month}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateMonth(string $month): void
    {
        $tz = config('tradesim.display_timezone');
        $valid = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)
            && Carbon::createFromFormat('Y-m-d', $month.'-01', $tz)->lte(now($tz));
        abort_unless($valid, 404);
    }
}
