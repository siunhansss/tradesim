<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case PartiallyFilled = 'partially_filled';
    case Filled = 'filled';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ khớp',
            self::PartiallyFilled => 'Khớp một phần',
            self::Filled => 'Đã khớp',
            self::Cancelled => 'Đã hủy',
            self::Rejected => 'Bị từ chối',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'primary',
            self::PartiallyFilled => 'warning',
            self::Filled => 'success',
            self::Cancelled => 'secondary',
            self::Rejected => 'danger',
        };
    }

    /** Lệnh còn mở thì còn được khớp tiếp hoặc hủy. */
    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::PartiallyFilled;
    }

    /** Các trạng thái được phép chuyển tới (sơ đồ trạng thái lệnh, docs/design). */
    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Pending => [self::PartiallyFilled, self::Filled, self::Cancelled, self::Rejected],
            self::PartiallyFilled => [self::PartiallyFilled, self::Filled, self::Cancelled],
            default => [],
        }, true);
    }
}
