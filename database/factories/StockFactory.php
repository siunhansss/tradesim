<?php

namespace Database\Factories;

use App\Enums\StockStatus;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    public function definition(): array
    {
        $reference = fake()->numberBetween(100, 1500) * 100;

        return [
            'symbol' => strtoupper(fake()->unique()->lexify('???')),
            'name' => 'CTCP '.fake()->company(),
            'exchange' => 'HOSE',
            'industry' => fake()->randomElement(['Ngân hàng', 'Bất động sản', 'Thép', 'Bán lẻ', 'Công nghệ']),
            'reference_price' => $reference,
            ...Stock::priceBand($reference),
            'last_price' => $reference,
            'volume' => 0,
            'last_price_at' => now(),
            'price_source' => 'Dữ liệu mẫu',
            'status' => StockStatus::Active,
        ];
    }

    public function halted(): static
    {
        return $this->state(fn () => ['status' => StockStatus::Halted]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['status' => StockStatus::Hidden]);
    }
}
