<?php

namespace App\Http\Controllers;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const SORTS = ['created_at', 'quantity', 'limit_price', 'status'];

    /** Sổ lệnh (FR-08, FR-12): lọc theo mã, trạng thái, chiều, loại, khoảng ngày; sắp xếp; phân trang. */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'in:'.implode(',', array_column(OrderStatus::cases(), 'value')).',open'],
            'side' => ['nullable', 'in:'.implode(',', array_column(OrderSide::cases(), 'value'))],
            'type' => ['nullable', 'in:'.implode(',', array_column(OrderType::cases(), 'value'))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
            'dir' => ['nullable', 'in:asc,desc'],
        ]);
        $tz = config('tradesim.display_timezone');

        $orders = Order::query()
            ->with('stock:id,symbol,name')
            ->where('user_id', $request->user()->id)
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->whereHas('stock', fn ($s) => $s->where('symbol', 'like', '%'.strtoupper($term).'%')))
            ->when($filters['status'] ?? null, fn ($q, $status) => $status === 'open'
                ? $q->whereIn('status', [OrderStatus::Pending->value, OrderStatus::PartiallyFilled->value])
                : $q->where('status', $status))
            ->when($filters['side'] ?? null, fn ($q, $side) => $q->where('side', $side))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from, $tz)->startOfDay()->utc()))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', Carbon::parse($to, $tz)->endOfDay()->utc()))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['dir'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate(config('tradesim.per_page'))
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'sort' => $filters['sort'] ?? 'created_at',
            'dir' => $filters['dir'] ?? 'desc',
            'openCount' => Order::query()->where('user_id', $request->user()->id)
                ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::PartiallyFilled->value])->count(),
        ]);
    }

    /** Hủy lệnh hoặc phần chưa khớp (FR-09, quy trình 5.5). Quyền kiểm tra bằng OrderPolicy::cancel. */
    public function destroy(Order $order, OrderService $orders): RedirectResponse
    {
        $filled = $order->filled_quantity;

        if (! $orders->cancel($order, auth()->user())) {
            return back()->with('error', "Lệnh #{$order->id} không còn ở trạng thái cho phép hủy.");
        }

        return back()->with('success', $filled > 0
            ? "Đã hủy phần chưa khớp của lệnh #{$order->id}. Giữ nguyên ".Format::money($filled).' cp đã khớp.'
            : "Đã hủy lệnh #{$order->id} và giải phóng tài sản đang giữ.");
    }

    /**
     * OrderService nhận qua tham số (không qua constructor) để mỗi request lấy tỷ lệ phí, thuế
     * mới nhất từ cấu hình; controller có thể được Laravel giữ lại giữa các request (BR-13).
     */
    public function store(PlaceOrderRequest $request, OrderService $orders): RedirectResponse
    {
        $stock = $request->stock();
        $result = $orders->place($request->user(), $stock, $request->validated());
        $order = $result->order;
        $back = redirect()->route('market.show', $stock->symbol);

        if ($result->duplicate) {
            return $back->with('warning', "Yêu cầu này đã được xử lý trước đó (lệnh #{$order->id}, {$order->status->label()}). Hệ thống không tạo lệnh mới.");
        }

        if ($result->rejected()) {
            return $back->withInput($request->except('request_key'))
                ->with('error', "Lệnh #{$order->id} bị từ chối: {$order->reject_reason}");
        }

        $summary = "{$order->side->label()} ".Format::money($order->quantity)." {$stock->symbol}";

        return $back->with('success', match ($order->status) {
            OrderStatus::Filled => "Lệnh #{$order->id} đã khớp: {$summary}, giá ".Format::money($order->avg_fill_price).' đ.',
            OrderStatus::PartiallyFilled => "Lệnh #{$order->id} khớp một phần: ".Format::money($order->filled_quantity).'/'.Format::money($order->quantity).' cp.',
            default => "Đã đặt lệnh #{$order->id}: {$summary} giá ".Format::money($order->limit_price).' đ, đang chờ khớp.',
        });
    }
}
