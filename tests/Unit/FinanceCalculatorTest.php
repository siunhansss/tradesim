<?php

namespace Tests\Unit;

use App\Services\FinanceCalculator;
use PHPUnit\Framework\TestCase;

/** Kiểm thử công thức mục 6.2 bằng bộ số liệu ví dụ mục 6.3 của đặc tả. */
class FinanceCalculatorTest extends TestCase
{
    private FinanceCalculator $calc;

    protected function setUp(): void
    {
        $this->calc = new FinanceCalculator(feeRate: 0.0015, sellTaxRate: 0.001);
    }

    public function test_buy_adds_rounded_up_fee(): void
    {
        $this->assertSame(['value' => 26_000_000, 'fee' => 39_000, 'tax' => 0, 'cash' => 26_039_000], $this->calc->buy(1000, 26_000));
        $this->assertSame(27_040_500, $this->calc->buy(1000, 27_000)['cash']);
    }

    public function test_fee_is_rounded_up_to_the_dong(): void
    {
        // 100 x 10.010 = 1.001.000; phí 0,15% = 1.501,5 -> 1.502
        $this->assertSame(1_502, $this->calc->fee(1_001_000));
    }

    public function test_sell_follows_section_6_3_example(): void
    {
        $result = $this->calc->sell(500, 28_000, holdingQuantity: 2000, holdingCost: 53_079_500);

        $this->assertSame(14_000_000, $result['value']);
        $this->assertSame(21_000, $result['fee']);
        $this->assertSame(14_000, $result['tax']);
        $this->assertSame(13_965_000, $result['cash']);
        $this->assertSame(13_269_875, $result['cost_of_sold']);
        $this->assertSame(695_125, $result['realized_pnl']);
    }

    public function test_full_example_satisfies_br_15(): void
    {
        $b1 = $this->calc->buy(1000, 26_000);
        $b2 = $this->calc->buy(1000, 27_000);
        $cost = $b1['cash'] + $b2['cash'];
        $sell = $this->calc->sell(500, 28_000, 2000, $cost);

        $remainingCost = $cost - $sell['cost_of_sold'];
        $cash = 100_000_000 - $b1['cash'] - $b2['cash'] + $sell['cash'];
        $stockValue = 1500 * 27_500;
        $unrealized = $stockValue - $remainingCost;
        $total = $cash + $stockValue;

        $this->assertSame(39_809_625, $remainingCost);
        $this->assertSame(60_885_500, $cash);
        $this->assertSame(1_440_375, $unrealized);
        $this->assertSame(102_135_500, $total);
        $this->assertEqualsWithDelta(2.14, $this->calc->roi($total, 100_000_000), 0.005);
        // BR-15: lãi đã thực hiện + chưa thực hiện = tổng tài sản - vốn đầu tư ròng
        $this->assertSame($total - 100_000_000, $sell['realized_pnl'] + $unrealized);
    }

    public function test_max_drawdown(): void
    {
        $this->assertEqualsWithDelta(4.17, $this->calc->maxDrawdown([100.0, 103.2, 101.5, 98.9, 102.1]), 0.005);
        $this->assertSame(0.0, $this->calc->maxDrawdown([100, 101, 102]));
    }
}
