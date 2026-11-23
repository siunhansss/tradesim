<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordUpdateRequest extends FormRequest
{
    /** Lỗi hiển thị trong một túi lỗi riêng để không lẫn với form hồ sơ. */
    protected $errorBag = 'password';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return ['current_password' => 'mật khẩu hiện tại', 'password' => 'mật khẩu mới'];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ];
    }
}
