<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Cấu hình thị trường (quy trình 5.8, FR-13). Tỷ lệ nhập theo phần trăm cho dễ đọc. */
class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Cho phép nhập dấu phẩy thập phân kiểu Việt Nam: 0,15 -> 0.15
        foreach (['fee_percent', 'tax_percent', 'volatility_percent'] as $key) {
            if ($this->has($key)) {
                $this->merge([$key => str_replace(',', '.', trim((string) $this->input($key)))]);
            }
        }
        $this->merge(['market_open' => $this->boolean('market_open')]);
    }

    public function rules(): array
    {
        return [
            'market_open' => ['required', 'boolean'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:1', 'decimal:0,3'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:1', 'decimal:0,3'],
            'initial_capital' => ['required', 'integer', 'min:1000000', 'max:10000000000', 'multiple_of:1000'],
            'max_fill_per_tick' => ['required', 'integer', 'min:100', 'max:100000', 'multiple_of:100'],
            'volatility_percent' => ['required', 'numeric', 'min:0.1', 'max:7', 'decimal:0,2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'market_open' => 'trạng thái phiên',
            'fee_percent' => 'phí giao dịch',
            'tax_percent' => 'thuế bán',
            'initial_capital' => 'vốn khởi tạo',
            'max_fill_per_tick' => 'khối lượng khớp tối đa',
            'volatility_percent' => 'biên dao động giá',
        ];
    }

    public function messages(): array
    {
        return [
            'fee_percent.max' => 'Phí giao dịch không được vượt quá 1%.',
            'tax_percent.max' => 'Thuế bán không được vượt quá 1%.',
            'max_fill_per_tick.multiple_of' => 'Khối lượng khớp tối đa phải là bội số của 100.',
        ];
    }

    /** Chuyển dữ liệu form sang giá trị lưu trong market_settings. */
    public function settings(): array
    {
        $rate = fn ($percent) => rtrim(rtrim(number_format((float) $percent / 100, 6, '.', ''), '0'), '.') ?: '0';

        return [
            'market_open' => $this->boolean('market_open') ? '1' : '0',
            'fee_rate' => $rate($this->input('fee_percent')),
            'sell_tax_rate' => $rate($this->input('tax_percent')),
            'initial_capital' => (string) (int) $this->input('initial_capital'),
            'max_fill_per_tick' => (string) (int) $this->input('max_fill_per_tick'),
            'price_volatility' => $rate($this->input('volatility_percent')),
        ];
    }
}
