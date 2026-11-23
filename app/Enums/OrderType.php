<?php

namespace App\Enums;

enum OrderType: string
{
    case Market = 'MP'; // lệnh thị trường, khớp ngay theo giá hiện tại
    case Limit = 'LO';  // lệnh giới hạn, chờ khớp

    public function label(): string
    {
        return $this === self::Market ? 'MP – Thị trường' : 'LO – Giới hạn';
    }
}
