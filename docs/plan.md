# Kế hoạch bắt kịp tiến độ (từ 23/11/2026)

Đặc tả v2.0 chốt ngày 27/10/2026. Đến 23/11 nhóm chưa có mã, tức đang trễ khoảng 4 tuần. Kế hoạch này giữ nguyên hạn nộp cuối tuần 10 (03/01/2027) và nén 5 sprint còn lại vào 6 tuần.

## Cắt phạm vi ngay

Theo mục 10.4 của đặc tả, các mục sau chuyển sang **hướng phát triển** và không làm nữa:

- REST API với Sanctum (FR-26)
- Bảng giá thời gian thực bằng Reverb (FR-25); bảng giá dùng nút tải lại hoặc tự tải lại 30 giây
- Giới hạn tỷ trọng (quy trình 5.9e)
- Biểu đồ MDD (vẫn giữ biểu đồ tổng tài sản)
- Gửi email thông báo; chỉ giữ thông báo trong ứng dụng

Lý do: bảng tiêu chí chấm của giảng viên không có các mục này, còn CRUD, tìm kiếm, phân trang, kiểm thử và làm việc nhóm thì có.

## Kế hoạch 6 tuần

| Tuần | Ngày | Mục tiêu | Mốc kiểm tra |
|---|---|---|---|
| 5 | 23/11 – 29/11 | Dựng kho GitHub từ bộ khung này; cả nhóm chạy được; đọc thiết kế; làm issue tuần 5 | Tag **v0.1.0**; mỗi người có ít nhất 1 pull request được gộp |
| 6 | 30/11 – 06/12 | Price Engine, cấu hình, ví, đặt lệnh MP đầu đến cuối, trang danh mục | Tag **v0.2.0**: mua bán MP chạy được |
| 7 | 07/12 – 13/12 | Lệnh LO, khớp một phần, hủy lệnh; sổ lệnh và sổ tiền có lọc, phân trang; quản lý người dùng | Tag **v0.3.0** (MVP); test giao dịch mục 11 đạt |
| 8 | 14/12 – 20/12 | Tổng quan tài chính, ảnh chụp cuối ngày, báo cáo tháng CSV; watchlist, cảnh báo giá, thông báo; đối soát; audit log | Tag **v0.4.0**; dừng thêm tính năng |
| 9 | 21/12 – 27/12 | Kiểm thử toàn bộ mục 11, sửa lỗi, minh chứng kiểm thử (ảnh chụp, kết quả `php artisan test`), README, thử cài từ máy mới | CI xanh; không còn lỗi nghiêm trọng |
| 10 | 28/12 – 03/01 | Tag **v1.0.0**, CHANGELOG, báo cáo, slide, video demo, tổng duyệt | Nộp bài |

## Nhịp làm việc

- Thứ Hai: họp 30 phút chọn issue cho tuần, ghi biên bản vào `docs/meetings/YYYY-MM-DD.md`.
- Thứ Năm: họp nhanh 15 phút, báo vướng mắc.
- Chủ nhật: gộp pull request, gắn tag nếu đạt mốc.
- Mỗi người gộp ít nhất 2 pull request mỗi tuần và review ít nhất 2 pull request của người khác. Đây là minh chứng cho tiêu chí "Làm việc nhóm".
