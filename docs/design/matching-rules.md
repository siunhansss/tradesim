# Quy tắc giá và khớp lệnh mô phỏng

Bổ sung cho mục 6.2 của đặc tả. Mã nguồn: `App\Services\PriceEngine`, `App\Services\LimitOrderMatcher`, `App\Services\MatchingService`.

## Sinh giá (Price Engine)

| Quy tắc | Giá trị mặc định | Khóa cấu hình |
|---|---|---|
| Chạy khi nào | Mỗi 30 giây, chỉ khi phiên mô phỏng mở | `market_open` |
| Mã được cập nhật | Chỉ mã trạng thái "Đang giao dịch" | — |
| Biên dao động mỗi lần | Ngẫu nhiên đều trong ±1,1% giá hiện tại | `price_volatility` |
| Làm tròn | Theo bước giá HOSE: 10 đ (< 10.000), 50 đ (< 50.000), 100 đ | — |
| Giới hạn | Không vượt trần, không thấp hơn sàn của phiên (±7% giá tham chiếu) | — |
| Nguồn ghi nhận | "Giá mô phỏng (Price Engine)", lưu thời điểm cập nhật (BR-12) | — |

Mỗi lần chạy ghi một dòng vào `job_runs` với trạng thái `ok`, `skipped` (phiên đóng) hoặc `failed`.

## Khớp lệnh

| Loại | Điều kiện khớp | Giá khớp | Khối lượng |
|---|---|---|---|
| MP mua/bán | Khớp ngay khi đặt | Giá hiện tại | Toàn bộ |
| LO mua | Giá hiện tại ≤ giá đặt | Giá hiện tại | Tối đa `max_fill_per_tick` (1.000 cp) mỗi lần cập nhật giá |
| LO bán | Giá hiện tại ≥ giá đặt | Giá hiện tại | Tối đa `max_fill_per_tick` (1.000 cp) mỗi lần cập nhật giá |

- Lệnh đặt trước được xét trước (theo số hiệu lệnh).
- Giới hạn khối lượng mỗi lần mô phỏng thanh khoản có hạn, nên lệnh lớn sẽ qua trạng thái **Khớp một phần**.
- Lệnh mua LO giữ tiền theo giá đặt; khi khớp ở giá thấp hơn, phần chênh lệch được trả lại vào số dư khả dụng.
- Mỗi lệnh khớp trong một transaction riêng, khóa theo thứ tự: lệnh, mã, ví, holding. Lỗi ở một lệnh được ghi vào `job_runs.details` và không chặn các lệnh khác.

## Chạy trên máy phát triển

```bash
php artisan tradesim:prices                    # cập nhật một lần
php artisan tradesim:prices --times=10 --sleep=3   # 10 lần, cách nhau 3 giây (demo)
php artisan schedule:work                      # chạy theo lịch, mỗi 30 giây
```
