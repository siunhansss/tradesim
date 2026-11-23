<?php

return [
    // Lưu thời gian ở UTC, hiển thị theo giờ Việt Nam.
    'display_timezone' => env('TRADESIM_DISPLAY_TIMEZONE', 'Asia/Ho_Chi_Minh'),

    // Số dòng mỗi trang cho các danh sách có phân trang.
    'per_page' => 15,

    // Dòng nhắc hiển thị trên mọi trang (mục 12.3).
    'disclaimer' => 'Môi trường mô phỏng phục vụ học tập. Số dư là tiền ảo, không nạp/rút, không quy đổi thành tiền thật.',
];
