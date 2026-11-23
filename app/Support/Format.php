<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** Định dạng hiển thị theo quy ước Việt Nam. */
class Format
{
    public static function money(int|float|string|null $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    public static function signed(int|float $value): string
    {
        return ($value > 0 ? '+' : '').self::money($value);
    }

    public static function percent(float $value, int $decimals = 2): string
    {
        return ($value > 0 ? '+' : '').number_format($value, $decimals, ',', '.').'%';
    }

    public static function datetime(?CarbonInterface $value, string $format = 'd/m/Y H:i:s'): string
    {
        return $value ? $value->copy()->setTimezone(config('tradesim.display_timezone'))->format($format) : '—';
    }
}
