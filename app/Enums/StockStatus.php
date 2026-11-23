<?php

namespace App\Enums;

enum StockStatus: string
{
    case Active = 'active';   // được giao dịch
    case Halted = 'halted';   // ngừng giao dịch, vẫn hiển thị
    case Hidden = 'hidden';   // ngừng hiển thị trên bảng giá

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang giao dịch',
            self::Halted => 'Ngừng giao dịch',
            self::Hidden => 'Ngừng hiển thị',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Halted => 'warning',
            self::Hidden => 'secondary',
        };
    }
}
