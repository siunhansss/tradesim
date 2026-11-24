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

// Chốt phiên lúc 14:45 giờ Việt Nam, thứ Hai đến thứ Sáu (issue #12).
Schedule::command('tradesim:close-session')
    ->weekdays()
    ->at('14:45')
    ->timezone('Asia/Ho_Chi_Minh');

// Đối soát dữ liệu lúc 15:10 giờ Việt Nam, sau chốt phiên (issue #20).
Schedule::command('tradesim:reconcile')
    ->weekdays()
    ->at('15:10')
    ->timezone('Asia/Ho_Chi_Minh');

// Chụp ảnh danh mục lúc 15:05 giờ Việt Nam (issue #16, BR-16). Chốt phiên cũng tự chụp.
Schedule::command('tradesim:snapshot')
    ->weekdays()
    ->at('15:05')
    ->timezone('Asia/Ho_Chi_Minh');

// Xử lý hàng đợi (cảnh báo giá) mỗi phút khi đang chạy schedule:work, không cần mở thêm cửa sổ queue:work.
Schedule::command('queue:work --stop-when-empty --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
