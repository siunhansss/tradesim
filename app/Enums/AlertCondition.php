<?php

namespace App\Enums;

enum AlertCondition: string
{
    case AtOrAbove = 'gte';
    case AtOrBelow = 'lte';

    public function label(): string
    {
        return $this === self::AtOrAbove ? 'Giá ≥ ngưỡng' : 'Giá ≤ ngưỡng';
    }

    public function isMet(int $price, int $target): bool
    {
        return $this === self::AtOrAbove ? $price >= $target : $price <= $target;
    }
}
