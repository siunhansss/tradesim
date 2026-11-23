<?php

namespace App\Enums;

enum AlertStatus: string
{
    case Active = 'active';
    case Triggered = 'triggered';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang bật',
            self::Triggered => 'Đã kích hoạt',
            self::Disabled => 'Đã tắt',
        };
    }
}
