<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\PlaceOrderRequest;
use App\Services\OrderService;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        $stock = $request->stock();
        $result = $this->orders->place($request->user(), $stock, $request->validated());
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
