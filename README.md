# TradeSim

Ứng dụng web mô phỏng đầu tư chứng khoán và quản lý danh mục tài chính bằng **tiền ảo**, xây dựng với Laravel 13. Đồ án học phần Phần mềm nguồn mở nâng cao.

> TradeSim chỉ phục vụ học tập. Không nhận tiền thật, không nạp/rút, không gửi lệnh đến công ty chứng khoán. Giá cổ phiếu là dữ liệu mô phỏng.

## Tính năng

| Phân hệ | Trạng thái |
|---|---|
| M01 Đăng ký, đăng nhập, đăng xuất, phân quyền Trader/Admin, giới hạn đăng nhập sai | Có (v0.1.0) |
| M02 Bảng giá: tìm kiếm, lọc ngành, sắp xếp, phân trang; chi tiết mã và biểu đồ | Có (v0.1.0) |
| M03 Ví ảo, cấp vốn khởi tạo một lần, sổ biến động tiền | Một phần (v0.1.0) |
| M10 Quản lý mã cổ phiếu: thêm, sửa, xóa, tìm kiếm, lọc, sắp xếp, phân trang, audit log | Có (v0.1.0) |
| M04 Đặt lệnh MP/LO, khớp một phần, hủy lệnh | Đang làm |
| M05, M06 Danh mục, tổng quan tài chính, báo cáo tháng CSV | Đang làm |
| M09 Watchlist, cảnh báo giá, thông báo | Đang làm |

## Yêu cầu hệ thống

- PHP 8.3 trở lên, Composer 2
- Một trong: SQLite (có sẵn, nhanh nhất), MySQL 8.4 / MariaDB 11.4, hoặc Docker (Laravel Sail)
- Không cần Node.js: giao diện dùng Bootstrap 5 và Chart.js qua CDN

## Cài đặt

```bash
git clone https://github.com/<to-chuc>/tradesim.git
cd tradesim
composer install
cp .env.example .env
php artisan key:generate
```

Chọn một cách cấu hình CSDL:

**Cách 1 – SQLite (thử nhanh trên máy cá nhân).** Giữ `DB_CONNECTION=sqlite` trong `.env`, rồi tạo file CSDL:

```bash
touch database/database.sqlite        # Windows PowerShell: ni database/database.sqlite
```

**Cách 2 – MySQL trên máy (Laragon, XAMPP...).** Tạo CSDL `tradesim` (utf8mb4) rồi sửa `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tradesim
DB_USERNAME=root
DB_PASSWORD=
```

**Cách 3 – Docker với Laravel Sail (giống môi trường CI).**

```bash
php artisan sail:install --with=mysql,mailpit
./vendor/bin/sail up -d
# sau đó thay "php artisan" bằng "./vendor/bin/sail artisan" ở các lệnh bên dưới
```

Tạo bảng, dữ liệu mẫu và chạy:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Mở http://127.0.0.1:8000

### Tài khoản demo

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Quản trị viên | admin@tradesim.test | admin12345 |
| Nhà đầu tư | minhanh@tradesim.test | trader12345 |
| Nhà đầu tư | quanghuy@tradesim.test | trader12345 |

Dữ liệu mẫu gồm 20 mã cổ phiếu với 30 phiên giá lịch sử do nhóm tự sinh (không phải giá thị trường thật), 30 tài khoản Trader để thử phân trang.

## Kiểm thử

```bash
php artisan test            # toàn bộ test (SQLite trong bộ nhớ)
vendor/bin/pint --test      # kiểm tra định dạng mã
```

CI trên GitHub Actions chạy cùng các bước này với MySQL 8.4 cho mỗi pull request.

## Cấu trúc mã

| Thư mục | Nội dung |
|---|---|
| `app/Enums` | Vai trò, trạng thái lệnh, loại bút toán... |
| `app/Models` | Model Eloquent và relationship |
| `app/Services` | Nghiệp vụ: `WalletService`, `FinanceCalculator`, `AuditLogger`, (sắp có) `OrderService`... |
| `app/Http/Controllers` | Controller mỏng, gọi Service |
| `app/Http/Requests` | Kiểm tra dữ liệu nhập (Form Request) |
| `resources/views` | Giao diện Blade, layout chung `layouts/app.blade.php` |
| `lang/vi` | Thông báo tiếng Việt |
| `docs/design` | ERD, use case, sơ đồ chức năng, sơ đồ tuần tự, danh sách route |
| `docs/plan.md`, `docs/backlog.md` | Kế hoạch và phân công |

## Đóng góp

Xem [CONTRIBUTING.md](CONTRIBUTING.md). Mọi thay đổi vào `main` đi qua pull request có review.

## Giấy phép

Mã nguồn phát hành theo [giấy phép MIT](LICENSE). Các thư viện sử dụng (Laravel, Bootstrap, Chart.js) đều theo giấy phép MIT.
