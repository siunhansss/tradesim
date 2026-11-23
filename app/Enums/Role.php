<?php

namespace App\Enums;

enum Role: string
{
    case Trader = 'trader';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Trader => 'Nhà đầu tư',
            self::Admin => 'Quản trị viên',
        };
    }
}
