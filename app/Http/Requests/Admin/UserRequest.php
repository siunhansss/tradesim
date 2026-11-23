<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/** Thêm/sửa người dùng (FR-13). Dùng chung cho store và update. */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        /** @var User|null $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target?->id)],
            // Thêm mới bắt buộc có mật khẩu; khi sửa để trống nghĩa là giữ nguyên.
            'password' => [$target ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::enum(Role::class)],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    /** Admin không được tự hạ quyền hoặc tự khóa tài khoản của mình. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $target = $this->route('user');
                if (! $target || $target->id !== $this->user()->id) {
                    return;
                }
                if ($this->input('role') !== Role::Admin->value) {
                    $validator->errors()->add('role', 'Bạn không thể tự bỏ quyền quản trị của chính mình.');
                }
                if ($this->input('status') !== UserStatus::Active->value) {
                    $validator->errors()->add('status', 'Bạn không thể tự khóa tài khoản của chính mình.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'họ tên', 'email' => 'email', 'password' => 'mật khẩu', 'role' => 'vai trò', 'status' => 'trạng thái'];
    }
}
