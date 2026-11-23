# Danh sách route, controller, view

Trạng thái: **Có** = đã có trong mã Sprint 1; **S2–S4** = sprint dự kiến làm. Người phụ trách theo mục 10.1 của đặc tả.

| Phân hệ | Method | URI | Tên route | Controller@action | View | Middleware | Phụ trách | Trạng thái |
|---|---|---|---|---|---|---|---|---|
| M02 | GET | `/market` | market.index | MarketController@index | market/index | — | TV2 | Có |
| M02 | GET | `/market/{stock:symbol}` | market.show | MarketController@show | market/show | — | TV2 | Có |
| M01 | GET/POST | `/login` | login | Auth\AuthenticatedSessionController@create/store | auth/login | guest | TV4 | Có |
| M01 | GET/POST | `/register` | register | Auth\RegisteredUserController@create/store | auth/register | guest, throttle:6,1 | TV4 | Có |
| M01 | POST | `/logout` | logout | Auth\AuthenticatedSessionController@destroy | — | auth | TV4 | Có |
| M06 | GET | `/dashboard` | dashboard | DashboardController (tổng quan tài sản qua PortfolioService) | dashboard | auth | TV3 | Có |
| M01 | GET/PATCH | `/profile` | profile.edit, profile.update | ProfileController@edit/update | profile/edit | auth | TV4 | Có |
| M01 | PUT | `/profile/password` | profile.password | ProfileController@updatePassword | profile/edit | auth | TV4 | Có |
| M03 | GET | `/wallet` | wallet.index | WalletController@index | wallet/index | auth, can:trade | TV1 | S2 |
| M04 | GET | `/orders` | orders.index | OrderController@index | orders/index | auth, can:trade | TV1 | S2 |
| M04 | POST | `/orders` | orders.store | OrderController@store (phiếu lệnh nằm trong market/show) | market/_order-ticket | auth, can:trade, throttle:orders | TV1 | Có |
| M04 | DELETE | `/orders/{order}` | orders.destroy | OrderController@destroy (hủy lệnh) | — | auth, can:cancel,order | TV1 | S3 |
| M04 | GET | `/trades` | trades.index | TradeController@index | trades/index | auth, can:trade | TV1 | S3 |
| M05 | GET | `/portfolio` | portfolio.index | PortfolioController@index | portfolio/index | auth, can:trade | TV3 | S2 |
| M06 | GET | `/finance` | finance.index | FinanceController@index | finance/index | auth, can:trade | TV3 | S3 |
| M06 | GET | `/finance/reports/{month}` | finance.report | FinanceReportController@show | finance/report | auth, can:trade | TV3 | S4 |
| M06 | GET | `/finance/reports/{month}/csv` | finance.report.csv | FinanceReportController@csv | — | auth, can:trade | TV3 | S4 |
| M09 | GET/POST/DELETE | `/watchlist` | watchlist.* | WatchlistController@index/store/destroy | watchlist/index | auth, can:trade | TV2 | S4 |
| M09 | resource | `/alerts` | alerts.* | PriceAlertController (CRUD) | alerts/* | auth, can:trade | TV2 | S4 |
| M09 | GET/POST | `/notifications` | notifications.* | NotificationController@index/markRead | notifications/index | auth | TV2 | S4 |
| M10 | resource | `/admin/stocks` | admin.stocks.* | Admin\StockController (CRUD) | admin/stocks/* | auth, can:admin | TV4 | Có |
| M10 | resource | `/admin/users` | admin.users.* | Admin\UserController (CRUD, khóa/mở) | admin/users/* | auth, can:admin | TV4 | S3 |
| M10 | GET/PUT | `/admin/settings` | admin.settings.* | Admin\SettingController@edit/update | admin/settings/edit | auth, can:admin | TV4 | S2 |
| M10 | GET | `/admin/audit-logs` | admin.audit.index | Admin\AuditLogController@index | admin/audit/index | auth, can:admin | TV4 | S3 |
| M10 | POST | `/admin/reconcile` | admin.reconcile | Admin\ReconcileController | admin/reconcile | auth, can:admin | TV1 | S4 |

## Lệnh Artisan và lịch chạy

| Lệnh | Việc | Lịch | Phụ trách | Trạng thái |
|---|---|---|---|---|
| `tradesim:prices` | Price Engine sinh giá mới, gọi job khớp lệnh chờ | Mỗi 30 giây trong phiên | TV2 | S2 |
| `tradesim:close-session` | Chốt giá đóng cửa, đặt giá tham chiếu mới, hủy lệnh LO còn treo | 14:45 thứ Hai–Sáu | TV2 | S3 |
| `tradesim:snapshot` | Chụp ảnh danh mục cuối ngày | 15:05 thứ Hai–Sáu | TV3 | S4 |
| `tradesim:reconcile` | Đối soát ví, sổ cái, lệnh, holdings, BR-15 | Hằng ngày và chạy tay | TV1 | S4 |
