<?php

namespace App\Services;

/**
 * Công thức tài chính theo mục 6.2 của đặc tả. Hàm thuần, không truy cập CSDL,
 * để kiểm thử đơn vị bằng bộ số liệu ở mục 6.3.
 *
 * Quy ước: tiền là số nguyên đồng; phí và thuế làm tròn LÊN theo từng lần khớp.
 */
class FinanceCalculator
{
    public function __construct(
        private float $feeRate = 0.0015,
        private float $sellTaxRate = 0.001,
    ) {}

    public function fee(int $value): int
    {
        return $this->roundUp($value * $this->feeRate);
    }

    public function sellTax(int $value): int
    {
        return $this->roundUp($value * $this->sellTaxRate);
    }

    /** Khớp mua: trả về giá trị, phí và tổng tiền chi. */
    public function buy(int $quantity, int $price): array
    {
        $value = $quantity * $price;
        $fee = $this->fee($value);

        return ['value' => $value, 'fee' => $fee, 'tax' => 0, 'cash' => $value + $fee];
    }

    /**
     * Khớp bán: trả về giá trị, phí, thuế, tiền thu về, giá vốn phần bán và lãi/lỗ đã thực hiện.
     *
     * @param  int  $holdingQuantity  số lượng nắm giữ TRƯỚC khi bán
     * @param  int  $holdingCost  tổng giá vốn TRƯỚC khi bán
     */
    public function sell(int $quantity, int $price, int $holdingQuantity, int $holdingCost): array
    {
        $value = $quantity * $price;
        $fee = $this->fee($value);
        $tax = $this->sellTax($value);
        $proceeds = $value - $fee - $tax;
        $costOfSold = (int) round($holdingCost * $quantity / $holdingQuantity);

        return [
            'value' => $value,
            'fee' => $fee,
            'tax' => $tax,
            'cash' => $proceeds,
            'cost_of_sold' => $costOfSold,
            'realized_pnl' => $proceeds - $costOfSold,
        ];
    }

    /** Tiền cần giữ cho lệnh mua: giá trị theo giá đặt cộng phí ước tính. */
    public function buyReservation(int $quantity, int $price): int
    {
        return $this->buy($quantity, $price)['cash'];
    }

    public function roi(int $totalAssets, int $netInvested): float
    {
        return $netInvested > 0 ? ($totalAssets - $netInvested) / $netInvested * 100 : 0.0;
    }

    /**
     * Mức sụt giảm tối đa (MDD) từ chuỗi tổng tài sản cuối ngày, đơn vị phần trăm.
     *
     * @param  array<int|float>  $series
     */
    public function maxDrawdown(array $series): float
    {
        $peak = null;
        $max = 0.0;
        foreach ($series as $value) {
            $peak = $peak === null ? $value : max($peak, $value);
            if ($peak > 0) {
                $max = max($max, ($peak - $value) / $peak * 100);
            }
        }

        return $max;
    }

    private function roundUp(float $amount): int
    {
        // Làm tròn 6 chữ số trước khi làm tròn lên, tránh lỗi dấu phẩy động (39000.0000001 -> 39001).
        return (int) ceil(round($amount, 6));
    }
}
