<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Hồ sơ cá nhân (M01): xem, sửa thông tin, đổi mật khẩu. */
class ProfileController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $changes = array_keys($user->getDirty());
        $user->save();

        if ($changes) {
            $this->audit->log('profile.update', $user, 'ok', ['fields' => $changes]);
        }

        return redirect()->route('profile.edit')->with('success', 'Đã cập nhật hồ sơ.');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);
        // Tạo session ID mới sau khi đổi mật khẩu.
        $request->session()->regenerate();

        $this->audit->log('profile.password', $user);

        return redirect()->route('profile.edit')->with('success', 'Đã đổi mật khẩu.');
    }
}
