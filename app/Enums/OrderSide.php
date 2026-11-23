<?php

namespace App\Enums;

enum OrderSide: string
{
    case Buy = 'buy';
    case Sell = 'sell';

    public function label(): string
    {
        return $this === self::Buy ? 'Mua' : 'Bán';
    }
}
