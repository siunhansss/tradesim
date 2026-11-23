<?php

use Illuminate\Support\Facades\Schedule;

/*
| Lịch chạy tác vụ định kỳ (mục 8.3). Trên máy dev chạy: php artisan schedule:work
| Price Engine tự bỏ qua khi phiên mô phỏng đóng, nên không cần giới hạn giờ ở đây;
| Admin mở/đóng phiên trong trang Cấu hình để demo bất kỳ lúc nào.
*/

Schedule::command('tradesim:prices')
    ->everyThirtySeconds()
    ->withoutOverlapping();
