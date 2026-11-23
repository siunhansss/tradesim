<?php

namespace Database\Seeders;

use App\Models\MarketSetting;
use Illuminate\Database\Seeder;

class MarketSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MarketSetting::DEFAULTS as $key => [$value, $description]) {
            MarketSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'description' => $description]);
        }
    }
}
