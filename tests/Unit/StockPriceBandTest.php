<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Stock;
use PHPUnit\Framework\TestCase;

class StockPriceBandTest extends TestCase
{
    public function test_tick_size_follows_hose_rules(): void
    {
        $this->assertSame(10, Stock::tickSize(9_870));
        $this->assertSame(50, Stock::tickSize(26_350));
        $this->assertSame(100, Stock::tickSize(118_500));
    }

    public function test_price_band_is_seven_percent_rounded_inwards(): void
    {
        // 26.350 x 1,07 = 28.194,5 -> 28.150 ; 26.350 x 0,93 = 24.505,5 -> 24.550
        $this->assertSame(['ceiling_price' => 28_150, 'floor_price' => 24_550], Stock::priceBand(26_350));
        $this->assertSame(['ceiling_price' => 126_700, 'floor_price' => 110_300], Stock::priceBand(118_500));
    }

    public function test_order_status_transitions(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::PartiallyFilled));
        $this->assertTrue(OrderStatus::PartiallyFilled->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Filled->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Pending));
    }
}
