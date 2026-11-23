# Changelog

Định dạng theo [Keep a Changelog](https://keepachangelog.com/vi/1.1.0/), phiên bản theo [Semantic Versioning](https://semver.org/lang/vi/).

## [Unreleased]

### Thêm
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
