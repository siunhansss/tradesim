<?php

namespace App\Http\Requests;

use App\Enums\AlertCondition;
use App\Enums\AlertStatus;
use App\Models\PriceAlert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Thêm/sửa cảnh báo giá (FR-24, BR-19). */
class PriceAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('trade') ?? false;
    }

    public function rules(): array
    {
        $creating = $this->route('alert') === null;

        return [
            'stock_id' => [$creating ? 'required' : 'prohibited', 'integer', Rule::exists('stocks', 'id')->where('status', 'active')],
            'condition' => ['required', Rule::enum(AlertCondition::class)],
            'target_price' => ['required', 'integer', 'min:1', 'max:10000000'],
            'status' => [$creating ? 'prohibited' : 'required', Rule::in([AlertStatus::Active->value, AlertStatus::Disabled->value])],
        ];
    }

    /** Tối đa 20 cảnh báo đang bật cho mỗi người dùng (BR-19). */
    public function after(): array
    {
        return [function (Validator $validator) {
            $willBeActive = $this->route('alert') === null || $this->input('status') === AlertStatus::Active->value;
            $current = $this->route('alert');
            $active = PriceAlert::query()->where('user_id', $this->user()->id)->where('status', AlertStatus::Active->value)
                ->when($current, fn ($q) => $q->whereKeyNot($current->id))->count();
            if ($willBeActive && $active >= PriceAlert::MAX_ACTIVE_PER_USER) {
                $validator->errors()->add('target_price', 'Bạn đã có '.PriceAlert::MAX_ACTIVE_PER_USER.' cảnh báo đang bật. Hãy tắt hoặc xóa bớt.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['stock_id' => 'mã cổ phiếu', 'condition' => 'điều kiện', 'target_price' => 'ngưỡng giá', 'status' => 'trạng thái'];
    }
}
