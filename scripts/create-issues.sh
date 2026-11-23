#!/usr/bin/env bash
# Tạo nhãn, milestone và issue trên GitHub từ docs/backlog.md.
# Yêu cầu: GitHub CLI (gh) đã đăng nhập, chạy trong thư mục kho mã.
# Cách dùng: bash scripts/create-issues.sh
set -euo pipefail

repo=$(gh repo view --json nameWithOwner -q .nameWithOwner)
echo "Kho: $repo"

for label in "owner:TV1" "owner:TV2" "owner:TV3" "owner:TV4" "owner:Cả nhóm" "type:feature" "type:bug" "type:docs"; do
  gh label create "$label" --force >/dev/null
done

for week in 5 6 7 8 9 10; do
  gh api "repos/$repo/milestones" -f title="Tuần $week" >/dev/null 2>&1 || true
done

grep -E '^\| [0-9]+ \|' docs/backlog.md | while IFS='|' read -r _ num week owner title req _; do
  num=$(echo "$num" | xargs); week=$(echo "$week" | xargs); owner=$(echo "$owner" | xargs)
  title=$(echo "$title" | sed 's/^ *//;s/ *$//'); req=$(echo "$req" | xargs)
  body=$(printf 'Yêu cầu liên quan: %s (xem đặc tả v2.0).\n\nPhụ trách: %s\n\n## Tiêu chí hoàn thành\n- [ ] Có test cho quy tắc nghiệp vụ liên quan\n- [ ] CI đạt (Pint + test)\n- [ ] Đã được một thành viên khác review\n- [ ] Cập nhật tài liệu nếu cần' "$req" "$owner")
  echo "Tạo issue #$num: $title"
  gh issue create --title "$title" --body "$body" --label "owner:$owner" --label "type:feature" --milestone "Tuần $week" >/dev/null
done

echo "Xong. Mỗi thành viên tự gán (assign) issue của mình trên GitHub."
