<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/** Ghi nhật ký kiểm toán (FR-14). Không bao giờ ghi mật khẩu hay token (mục 8.2). */
class AuditLogger
{
    private const SECRET_KEYS = ['password', 'password_confirmation', 'token', '_token', 'remember_token'];

    public function log(string $action, ?Model $subject = null, string $result = 'ok', array $details = [], ?User $actor = null): AuditLog
    {
        $actor ??= Auth::user();

        return AuditLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'result' => $result,
            'details' => Arr::except($details, self::SECRET_KEYS) ?: null,
            'ip_address' => Request::ip(),
        ]);
    }
}
