<?php

namespace App\Services;

use App\Enums\LedgerType;
use App\Models\MarketSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Quản lý ví ảo (M03). Mọi thay đổi số dư đi kèm một bút toán trong cùng transaction.
 */
class WalletService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Tạo ví và cấp vốn khởi tạo đúng một lần cho mỗi tài khoản (BR-01, quy trình 5.1).
     * Gọi lại nhiều lần vẫn an toàn: lần sau chỉ trả về ví đã có.
     */
    public function openWithInitialCapital(User $user): Wallet
    {
        return DB::transaction(function () use ($user) {
            $wallet = Wallet::query()->firstOrCreate(['user_id' => $user->id]);
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->first();

            $key = 'INIT:'.$user->id;
            if (WalletTransaction::query()->where('idempotency_key', $key)->exists()) {
                $this->audit->log('wallet.initial_capital', $wallet, 'skipped', ['reason' => 'Đã cấp trước đó'], $user);

                return $wallet;
            }

            $amount = MarketSetting::initialCapital();
            $wallet->balance += $amount;
            $wallet->save();

            $this->record($wallet, LedgerType::Init, $amount, note: 'Vốn mô phỏng khởi tạo', idempotencyKey: $key);
            $this->audit->log('wallet.initial_capital', $wallet, 'ok', ['amount' => $amount], $user);

            return $wallet;
        });
    }

    /**
     * Ghi một bút toán phản ánh trạng thái ví hiện tại.
     * Hàm này phải được gọi bên trong DB::transaction, sau khi đã cập nhật $wallet.
     */
    public function record(
        Wallet $wallet,
        LedgerType $type,
        int $amount,
        ?object $reference = null,
        ?string $note = null,
        ?string $idempotencyKey = null,
    ): WalletTransaction {
        return $wallet->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'reserved_after' => $wallet->reserved,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
            'idempotency_key' => $idempotencyKey,
            'note' => $note,
        ]);
    }
}
