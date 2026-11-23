<?php

namespace App\Http\Requests;

use App\Enums\OrderSide;
use App\Enums\OrderType;
use App\Models\Stock;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Kiểm tra dữ liệu phiếu đặt lệnh (FR-06, FR-07, BR-03). */
class PlaceOrderRequest extends FormRequest
{
    public const LOT_SIZE = 100;

    public const MAX_QUANTITY = 1_000_000;

    public function authorize(): bool
    {
        return $this->user()?->can('trade') ?? false;
    }

    public function rules(): array
    {
        return [
            'stock_id' => ['required', 'integer', Rule::exists('stocks', 'id')],
            'side' => ['required', Rule::enum(OrderSide::class)],
            'type' => ['required', Rule::enum(OrderType::class)],
            'quantity' => ['required', 'integer', 'min:'.self::LOT_SIZE, 'max:'.self::MAX_QUANTITY, 'multiple_of:'.self::LOT_SIZE],
            'limit_price' => ['exclude_unless:type,'.OrderType::Limit->value, 'required', 'integer', 'min:1', $this->limitPriceRule()],
            'request_key' => ['required', 'string', 'max:64'],
        ];
    }

    public function attributes(): array
    {
        return [
            'stock_id' => 'mã cổ phiếu',
            'side' => 'chiều lệnh',
            'type' => 'loại lệnh',
            'quantity' => 'khối lượng',
            'limit_price' => 'giá đặt',
            'request_key' => 'mã yêu cầu',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.multiple_of' => 'Khối lượng phải là bội số của 100 cổ phiếu (lô chẵn).',
            'quantity.min' => 'Khối lượng tối thiểu là 100 cổ phiếu.',
            'limit_price.required' => 'Vui lòng nhập giá đặt cho lệnh giới hạn (LO).',
        ];
    }

    public function stock(): Stock
    {
        return Stock::query()->findOrFail($this->integer('stock_id'));
    }

    /** Giá đặt phải nằm trong biên độ trần/sàn và theo bước giá. */
    private function limitPriceRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $stock = Stock::query()->find($this->integer('stock_id'));
            if (! $stock || ! is_numeric($value)) {
                return;
            }
            $price = (int) $value;
            if ($price > $stock->ceiling_price || $price < $stock->floor_price) {
                $fail('Giá đặt phải nằm trong biên độ từ '.number_format($stock->floor_price, 0, ',', '.')
                    .' đến '.number_format($stock->ceiling_price, 0, ',', '.').' đ.');

                return;
            }
            $tick = Stock::tickSize($price);
            if ($price % $tick !== 0) {
                $fail("Giá đặt phải là bội số của bước giá {$tick} đồng.");
            }
        };
    }
}
