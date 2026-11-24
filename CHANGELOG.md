# Changelog

Định dạng theo [Keep a Changelog](https://keepachangelog.com/vi/1.1.0/), phiên bản theo [Semantic Versioning](https://semver.org/lang/vi/).

## [Unreleased]

## [0.4.0] – 2026-12-06

Dừng thêm tính năng (feature freeze). Từ bản này chỉ sửa lỗi, kiểm thử và hoàn thiện tài liệu.

### Thêm
- Ảnh chụp danh mục cuối ngày: lệnh `tradesim:snapshot`, lịch 15:05, tự chụp khi chốt phiên; mỗi ngày một bản ghi (#16, BR-16).
- Trang Tài chính: biểu đồ tổng tài sản theo ngày, đỉnh tài sản, mức sụt giảm tối đa (MDD) (#16).
- Báo cáo tháng và xuất CSV (UTF-8, mở được bằng Excel); số liệu CSV trùng với màn hình (#17).
- Danh sách theo dõi và cảnh báo giá: thêm, sửa, bật/tắt, xóa; tối đa 20 cảnh báo đang bật (#18, BR-19).
- Kiểm tra cảnh báo qua Queue (`EvaluatePriceAlerts`, `ShouldBeUnique`); thông báo khớp lệnh qua Event `OrderFilled` + Listener; trang Thông báo (#19, BR-20).
- Đối soát dữ liệu: lệnh `tradesim:reconcile`, lịch 15:10, trang Admin; kiểm tra sổ cái, tiền giữ, nắm giữ và BR-15 (#20).
- Trang nhật ký kiểm toán cho Admin: lọc theo hành động, kết quả, người thực hiện, khoảng ngày; phân trang (#21).

### Thay đổi
- Lệnh LO đặt giá mua ≥ (hoặc bán ≤) giá hiện tại được khớp ngay khi đặt, tối đa 1.000 cp; phần còn lại chờ Price Engine.
- `schedule:work` tự xử lý hàng đợi mỗi phút, không cần mở thêm cửa sổ `queue:work`.

## [0.3.0] – 2026-11-29

Bản MVP: đủ luồng giao dịch đặt – khớp – hủy, sổ lệnh, sổ tiền, quản trị người dùng.

### Thêm
- Hủy lệnh và hủy phần chưa khớp của lệnh khớp một phần; giải phóng tiền hoặc cổ phiếu đang giữ; `OrderPolicy` (#10).
- Sổ lệnh và lịch sử giao dịch khớp: lọc theo mã, trạng thái, chiều, loại, khoảng ngày; sắp xếp; phân trang; tổng theo bộ lọc (#11).
- Chốt phiên: lệnh `tradesim:close-session`, lịch 14:45, nút cho Admin; hủy lệnh LO còn mở, giá đóng cửa thành giá tham chiếu mới (#12).
- Sổ dòng tiền: lọc theo kỳ và loại, số dư đầu kỳ, cuối kỳ, tiền vào, tiền ra, kiểm tra khớp, phân trang (#13).
- Quản lý người dùng cho Admin: thêm, sửa, khóa/mở, đổi vai trò, đặt lại mật khẩu, xóa có ràng buộc, tìm kiếm, lọc, sắp xếp, phân trang (#14).
- Price Engine: lệnh `tradesim:prices`, lịch chạy mỗi 30 giây, ghi lịch sử giá và `job_runs` (#5).
- Tự động khớp lệnh LO khi giá chạm giá đặt, tối đa 1.000 cp mỗi lần, có khớp một phần (#9).
- Trang cấu hình thị trường cho Admin: mở/đóng phiên, phí, thuế, vốn khởi tạo, biên dao động; nút chạy cập nhật giá; bảng tác vụ nền (#6).
- Trang danh mục: nắm giữ, giá vốn, lãi/lỗ, tỷ trọng, sắp xếp, biểu đồ phân bổ, vị thế đã bán hết (#8).
- Tài liệu quy tắc giá và khớp lệnh `docs/design/matching-rules.md`.

### Sửa
- `OrderController` nhận `OrderService` theo từng request để luôn dùng tỷ lệ phí mới nhất (BR-13).

## [0.2.0] – 2026-11-23

### Thêm
- Đặt lệnh MP và LO từ trang chi tiết mã; lệnh MP khớp ngay, lệnh LO giữ tiền hoặc cổ phiếu và chờ khớp (#3, #7).
- `OrderService`, `MatchingService`: transaction, khóa hàng, chống trùng `request_key`, ghi sổ tiền và audit log.
- `PortfolioService`: tổng tài sản, lãi/lỗ đã và chưa thực hiện, ROI, tỷ trọng; trang tổng quan hiển thị danh mục (#4).
- Trang hồ sơ: sửa họ tên, email; đổi mật khẩu (#2).
- Test đầu cuối chạy lại ví dụ mục 6.3 của đặc tả qua HTTP.

## [0.1.0] – 2026-11-29

### Thêm
- Mô hình dữ liệu: 15 bảng nghiệp vụ, migration, model, relationship, enum (ERD trong `docs/design`).
- Đăng ký, đăng nhập, đăng xuất; giới hạn 5 lần đăng nhập sai mỗi phút; khóa tài khoản.
- Phân quyền Trader/Admin bằng Gate; trang lỗi 403, 404, 419, 500 tiếng Việt.
- Ví ảo, cấp vốn khởi tạo một lần có khóa chống trùng.
- Bảng giá: tìm kiếm, lọc ngành, sắp xếp, phân trang; chi tiết mã có biểu đồ.
- Quản trị mã cổ phiếu: thêm, sửa, xóa có ràng buộc, tìm kiếm, lọc, sắp xếp, phân trang, audit log.
- Dữ liệu mẫu 20 mã, 30 phiên giá, tài khoản demo.
- `FinanceCalculator` theo mục 6.2 đặc tả, test bằng ví dụ mục 6.3.
- CI GitHub Actions (Pint + test trên MySQL 8.4), mẫu issue và pull request.
