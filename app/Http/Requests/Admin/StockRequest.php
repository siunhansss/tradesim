<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockStatus;
use App\Models\Stock;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Kiểm tra dữ liệu thêm/sửa mã cổ phiếu (dùng chung cho store và update). */
class StockRequest extends FormRequest
{
    public const EXCHANGES = ['HOSE', 'HNX', 'UPCOM'];

    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'symbol' => strtoupper(trim((string) $this->input('symbol'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        /** @var Stock|null $stock */
        $stock = $this->route('stock');

        return [
            'symbol' => ['required', 'string', 'regex:/^[A-Z0-9]{3}$/', Rule::unique('stocks', 'symbol')->ignore($stock?->id)],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'exchange' => ['required', Rule::in(self::EXCHANGES)],
            'industry' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reference_price' => ['required', 'integer', 'min:1000', 'max:10000000', $this->tickRule()],
            'status' => ['required', Rule::enum(StockStatus::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'symbol' => 'mã cổ phiếu',
            'name' => 'tên doanh nghiệp',
            'exchange' => 'sàn',
            'industry' => 'ngành',
            'description' => 'mô tả',
            'reference_price' => 'giá tham chiếu',
            'status' => 'trạng thái',
        ];
    }

    public function messages(): array
    {
        return [
            'symbol.regex' => 'Mã cổ phiếu gồm đúng 3 ký tự chữ in hoa hoặc số, ví dụ: VNM.',
            'symbol.unique' => 'Mã :input đã tồn tại.',
        ];
    }

    private function tickRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! is_numeric($value)) {
                return;
            }
            $tick = Stock::tickSize((int) $value);
            if ((int) $value % $tick !== 0) {
                $fail("Giá tham chiếu phải là bội số của bước giá {$tick} đồng.");
            }
        };
    }
}
