<?php

namespace Database\Seeders;

use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * 20 mã mẫu. Giá tham chiếu là số liệu nhóm tự đặt để mô phỏng, không phải giá thị trường thật (BR-12).
 * Lịch sử giá 30 phiên được sinh ngẫu nhiên có hạt giống cố định để mọi máy ra cùng dữ liệu.
 */
class StockSeeder extends Seeder
{
    private const STOCKS = [
        ['VNM', 'CTCP Sữa Việt Nam', 'Thực phẩm', 61800],
        ['FPT', 'CTCP FPT', 'Công nghệ', 118500],
        ['HPG', 'CTCP Tập đoàn Hòa Phát', 'Thép', 26350],
        ['VCB', 'NH TMCP Ngoại thương Việt Nam', 'Ngân hàng', 89400],
        ['MWG', 'CTCP Đầu tư Thế Giới Di Động', 'Bán lẻ', 58200],
        ['VIC', 'Tập đoàn Vingroup', 'Bất động sản', 42150],
        ['SSI', 'CTCP Chứng khoán SSI', 'Chứng khoán', 31700],
        ['GAS', 'TCT Khí Việt Nam', 'Năng lượng', 69900],
        ['TCB', 'NH TMCP Kỹ Thương Việt Nam', 'Ngân hàng', 23450],
        ['MSN', 'CTCP Tập đoàn Masan', 'Tiêu dùng', 74300],
        ['ACB', 'NH TMCP Á Châu', 'Ngân hàng', 24800],
        ['VHM', 'CTCP Vinhomes', 'Bất động sản', 40500],
        ['PNJ', 'CTCP Vàng bạc Đá quý Phú Nhuận', 'Bán lẻ', 96200],
        ['REE', 'CTCP Cơ Điện Lạnh', 'Tiện ích', 67100],
        ['DGC', 'CTCP Tập đoàn Hóa chất Đức Giang', 'Hóa chất', 104000],
        ['POW', 'TCT Điện lực Dầu khí Việt Nam', 'Năng lượng', 12350],
        ['HDB', 'NH TMCP Phát triển TP.HCM', 'Ngân hàng', 27900],
        ['VJC', 'CTCP Hàng không VietJet', 'Hàng không', 102500],
        ['KDH', 'CTCP Đầu tư Kinh doanh Nhà Khang Điền', 'Bất động sản', 33600],
        ['HAG', 'CTCP Hoàng Anh Gia Lai', 'Nông nghiệp', 9870],
    ];

    public function run(): void
    {
        mt_srand(20261123);
        $start = Carbon::now('Asia/Ho_Chi_Minh')->startOfDay()->subDays(42);

        foreach (self::STOCKS as [$symbol, $name, $industry, $reference]) {
            $price = $reference;
            $rows = [];
            $day = $start->copy();
            for ($i = 0; $i < 30; $i++) {
                do {
                    $day->addDay();
                } while ($day->isWeekend());
                $band = Stock::priceBand($price);
                $next = $price * (1 + (mt_rand(-200, 200) / 10000));
                $next = (int) (round($next / Stock::tickSize((int) $next)) * Stock::tickSize((int) $next));
                $price = max($band['floor_price'], min($band['ceiling_price'], $next));
                $rows[] = [
                    'price' => $price,
                    'volume' => mt_rand(500, 20000) * 100,
                    'source' => 'Dữ liệu mẫu',
                    // Giá đóng cửa lúc 14:45 giờ Việt Nam, lưu theo UTC.
                    'recorded_at' => $day->copy()->setTime(14, 45)->utc(),
                ];
            }

            // Giá đóng cửa phiên gần nhất là giá tham chiếu của phiên hiện tại.
            $close = end($rows)['price'];
            $stock = Stock::query()->updateOrCreate(['symbol' => $symbol], [
                'name' => $name,
                'exchange' => 'HOSE',
                'industry' => $industry,
                'reference_price' => $close,
                ...Stock::priceBand($close),
                'last_price' => $close,
                'volume' => 0,
                'last_price_at' => end($rows)['recorded_at'],
                'price_source' => 'Dữ liệu mẫu',
                'status' => 'active',
            ]);
            $stock->prices()->delete();
            $stock->prices()->createMany($rows);
        }
    }
}
