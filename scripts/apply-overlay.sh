#!/usr/bin/env bash
# Chép bộ mã TradeSim lên một dự án Laravel 13 mới tạo, rồi cấu hình .env.
# Chỉ trưởng nhóm (hoặc TV2) chạy MỘT lần khi dựng kho mã. Các thành viên khác chỉ cần git clone.
#
# Cách dùng (Git Bash, macOS hoặc Linux):
#   composer create-project laravel/laravel tradesim "^13.0"
#   bash tradesim-source/scripts/apply-overlay.sh tradesim-source tradesim
set -euo pipefail

src=${1:?Thiếu thư mục nguồn (bộ mã TradeSim)}
dst=${2:?Thiếu thư mục dự án Laravel đích}

[ -f "$dst/artisan" ] || { echo "Không thấy $dst/artisan. Hãy tạo dự án bằng composer create-project trước."; exit 1; }

echo "Chép mã nguồn TradeSim vào $dst ..."
cp -R "$src/." "$dst/"

cd "$dst"
# Bỏ test mẫu của Laravel: trang "/" đã chuyển hướng sang bảng giá nên test này không còn đúng.
rm -f tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php
for f in .env.example .env; do
  [ -f "$f" ] || continue
  sed -i.bak \
    -e 's/^APP_NAME=.*/APP_NAME=TradeSim/' \
    -e 's/^APP_LOCALE=.*/APP_LOCALE=vi/' \
    -e 's/^APP_FAKER_LOCALE=.*/APP_FAKER_LOCALE=vi_VN/' \
    "$f" && rm -f "$f.bak"
done

echo "Định dạng mã theo chuẩn của Laravel Pint ..."
vendor/bin/pint >/dev/null || true

cat <<'MSG'

Xong. Bước tiếp theo:
  1. Cấu hình CSDL trong .env (xem README, mục Cài đặt).
  2. php artisan migrate:fresh --seed
  3. php artisan test
  4. php artisan serve  rồi mở http://127.0.0.1:8000
  5. git init && git add . && git commit -m "chore: khởi tạo TradeSim v0.1.0"
MSG
