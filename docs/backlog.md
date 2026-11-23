# Backlog theo thành viên

Tạo tự động trên GitHub bằng `scripts/create-issues.sh` (cần GitHub CLI `gh` đã đăng nhập). Mỗi issue ghi mã yêu cầu FR/BR trong đặc tả.

Vai trò: **TV1** trưởng nhóm, backend giao dịch · **TV2** dữ liệu giá, hạ tầng, cảnh báo · **TV3** tài chính, kiểm thử · **TV4** tài khoản, quản trị, giao diện, tài liệu.

| # | Tuần | Người | Issue | Yêu cầu |
|---|---|---|---|---|
| 1 | 5 | TV2 | Dựng kho GitHub từ bộ khung, bật bảo vệ nhánh main, chạy CI lần đầu | 13.2, 13.4 |
| 2 | 5 | TV4 | Kiểm tra giao diện đăng nhập, đăng ký trên điện thoại; thêm trang hồ sơ, đổi mật khẩu | FR-01 |
| 3 | 5 | TV1 | Viết `OrderService` khung, `PlaceOrderRequest` (KL bội 100, giá trong biên độ, bước giá) và test kiểm tra dữ liệu | FR-06, BR-03 |
| 4 | 5 | TV3 | Viết `PortfolioService::summary()` dùng `FinanceCalculator`; test với dữ liệu factory | FR-11, 6.2 |
| 5 | 6 | TV2 | Lệnh `tradesim:prices` (Price Engine), Scheduler, ghi `stock_prices`, `job_runs` | 5.6, BR-12 |
| 6 | 6 | TV4 | Trang cấu hình thị trường (mở/đóng phiên, phí, thuế, vốn) có audit log | FR-13, FR-14 |
| 7 | 6 | TV1 | Đặt lệnh MP mua và bán: transaction, `lockForUpdate`, bút toán, holding, giá vốn (đã gộp vào #3, đóng cùng #3) | FR-06, FR-07, FR-10, BR-07 |
| 8 | 6 | TV3 | Trang danh mục: nắm giữ, giá vốn, giá trị, lãi/lỗ | FR-11 |
| 9 | 7 | TV1 | Lệnh LO, job `ProcessPendingOrders`, khớp một phần | FR-08, 5.6 |
| 10 | 7 | TV1 | Hủy lệnh, `OrderPolicy::cancel`, giải phóng phần chưa khớp | FR-09, BR-06 |
| 11 | 7 | TV1 | Sổ lệnh: lọc theo mã, trạng thái, ngày; sắp xếp; phân trang | FR-12 |
| 12 | 7 | TV2 | `tradesim:close-session`: giá đóng cửa, tham chiếu mới, hủy LO treo | 5.6 |
| 13 | 7 | TV3 | Sổ dòng tiền: lọc theo kỳ, loại; số dư đầu kỳ, cuối kỳ; phân trang | FR-18 |
| 14 | 7 | TV4 | CRUD người dùng (khóa/mở, đổi vai trò), tìm kiếm, phân trang | FR-13 |
| 15 | 7 | TV3 | Test chống trùng `request_key` và test hai lệnh đồng thời trên MySQL | BR-05, BR-08 |
| 16 | 8 | TV3 | `tradesim:snapshot` và biểu đồ tổng tài sản | FR-19, BR-16 |
| 17 | 8 | TV3 | Báo cáo tháng và xuất CSV | FR-20 |
| 18 | 8 | TV2 | Watchlist (thêm/xóa) và CRUD cảnh báo giá | FR-23, FR-24 |
| 19 | 8 | TV2 | Job `EvaluatePriceAlerts`, Notification kênh database, trang thông báo | FR-24, BR-19, BR-20 |
| 20 | 8 | TV1 | Lệnh `tradesim:reconcile` và nút chạy đối soát cho Admin | 8.1, BR-15 |
| 21 | 8 | TV4 | Trang xem audit log: lọc theo hành động, người dùng, ngày; phân trang | FR-14 |
| 22 | 9 | TV3 | Rà soát toàn bộ tiêu chí mục 11, bổ sung test còn thiếu, chụp minh chứng | Mục 11 |
| 23 | 9 | TV4 | README, hướng dẫn sử dụng có ảnh chụp màn hình, thử cài từ máy mới | 1.6 |
| 24 | 9 | TV2 | Đo chỉ tiêu mục 8.4, sửa lỗi N+1, thêm index nếu chậm | 8.4 |
| 25 | 10 | TV1 | Gắn tag v1.0.0, CHANGELOG, tổng hợp số liệu đóng góp từ GitHub | 13.2, 15.2 |
| 26 | 10 | Cả nhóm | Báo cáo, slide, video demo theo mục 15 | Mục 15 |
