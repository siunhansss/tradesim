# Tài liệu thiết kế TradeSim

Các sơ đồ viết bằng Mermaid (file `.mmd`), sửa trực tiếp được và GitHub tự hiển thị. File `.png` dùng để chèn vào báo cáo. Sau khi sửa `.mmd`, tạo lại ảnh bằng:

```bash
npx -p @mermaid-js/mermaid-cli mmdc -i docs/design/erd.mmd -o docs/design/erd.png -b white -s 2
```

| Sơ đồ | File | Dùng cho tiêu chí chấm |
|---|---|---|
| Use case | `usecase.mmd`, `usecase.png` | Tài liệu/sơ đồ thiết kế: use case |
| Sơ đồ chức năng (phân rã) | `functions.mmd`, `functions.png` | Tài liệu/sơ đồ thiết kế: sơ đồ chức năng. Ô xanh lá là chức năng đã có từ Sprint 1 |
| ERD | `erd.mmd`, `erd.png` | Thiết kế CSDL có ERD và quan hệ |
| Tuần tự đặt lệnh | `order-sequence.mmd`, `order-sequence.png` | Phân tích luồng xử lý cho M04 |
| Trạng thái lệnh | `order-state.mmd`, `order-state.png` | Khớp với `App\Enums\OrderStatus::canTransitionTo()` |
| Danh sách route | `routes.md` | Tổ chức route, controller, view |

ERD khớp với các migration trong `database/migrations`. Khi đổi migration, cập nhật `erd.mmd` trong cùng pull request.
