# Hướng dẫn đóng góp

## Quy trình

1. Chọn một issue trên bảng công việc (GitHub Projects), tự gán cho mình, chuyển sang cột **Đang làm**.
2. Tạo nhánh từ `main` mới nhất:
   ```bash
   git switch main && git pull
   git switch -c feature/12-so-lenh-loc-phan-trang
   ```
   Dùng `feature/` cho tính năng, `fix/` cho sửa lỗi, `docs/` cho tài liệu, kèm số issue.
3. Commit nhỏ, theo [Conventional Commits](https://www.conventionalcommits.org/):
   ```
   feat(orders): lọc sổ lệnh theo trạng thái (#12)
   fix(wallet): không cấp vốn hai lần khi tải lại trang (#7)
   test(finance): thêm test ví dụ mục 6.3
   docs(readme): hướng dẫn cài bằng Laragon
   ```
4. Trước khi đẩy lên: `vendor/bin/pint` và `php artisan test`.
5. Mở pull request, điền mẫu, ghi `Closes #12`. Nhờ một thành viên khác review.
6. Khi CI đạt và có một lượt duyệt, gộp bằng **Squash and merge**.

## Quy ước mã

- Controller mỏng; nghiệp vụ nằm trong `app/Services`.
- Kiểm tra dữ liệu bằng Form Request; thông báo lỗi tiếng Việt trong `lang/vi`.
- Phân quyền bằng Gate (`admin`, `trade`) hoặc Policy; không kiểm tra vai trò rải rác trong view.
- Mọi thay đổi tiền, cổ phiếu, trạng thái lệnh chạy trong `DB::transaction` và khóa hàng bằng `lockForUpdate()` theo thứ tự: ví trước, holding sau.
- Tiền là số nguyên đồng (cột `DECIMAL(18,0)`, cast `integer`). Không dùng `float` cho tiền.
- Không sửa migration đã gộp vào `main`; tạo migration mới.
- Tránh N+1: dùng `with()`; môi trường dev đã bật `preventLazyLoading`.

## Review

Người review kiểm tra: đúng yêu cầu trong issue, có test, không lộ dữ liệu nhạy cảm, đặt tên dễ hiểu, giao diện có thông báo lỗi rõ ràng. Góp ý cụ thể, lịch sự, kèm gợi ý sửa.

## Phát hành

Cuối mỗi mốc, trưởng nhóm cập nhật `CHANGELOG.md` và gắn tag theo Semantic Versioning (`v0.1.0`, `v0.2.0`, ..., `v1.0.0`).
