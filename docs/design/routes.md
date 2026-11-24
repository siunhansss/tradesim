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
| M03 | GET | `/wallet` | wallet.index | WalletController@index (sổ dòng tiền, lọc kỳ, phân trang) | wallet/index | auth, can:trade | TV3 | Có |
| M04 | GET | `/orders` | orders.index | OrderController@index (lọc, sắp xếp, phân trang) | orders/index | auth, can:trade | TV1 | Có |
| M04 | POST | `/orders` | orders.store | OrderController@store (phiếu lệnh nằm trong market/show) | market/_order-ticket | auth, can:trade, throttle:orders | TV1 | Có |
| M04 | DELETE | `/orders/{order}` | orders.destroy | OrderController@destroy (hủy lệnh) | — | auth, can:cancel,order | TV1 | Có |
| M04 | GET | `/trades` | trades.index | TradeController@index (lọc, sắp xếp, phân trang, tổng) | trades/index | auth, can:trade | TV1 | Có |
| M05 | GET | `/portfolio` | portfolio.index | PortfolioController@index (sắp xếp, biểu đồ phân bổ) | portfolio/index | auth, can:trade | TV3 | Có |
| M06 | GET | `/finance` | finance.index | FinanceController@index (biểu đồ tổng tài sản, MDD) | finance/index | auth, can:trade | TV3 | Có |
| M06 | GET | `/finance/reports/{month}` | finance.report | FinanceController@report | finance/report | auth, can:trade | TV3 | Có |
| M06 | GET | `/finance/reports/{month}/csv` | finance.report.csv | FinanceController@csv | — | auth, can:trade | TV3 | Có |
| M09 | GET/POST/DELETE | `/watchlist` | watchlist.* | WatchlistController@index/store/destroy | watchlist/index | auth, can:trade | TV2 | Có |
| M09 | resource | `/alerts` | alerts.* | PriceAlertController (CRUD, PriceAlertPolicy) | alerts/* | auth, can:trade | TV2 | Có |
| M09 | GET/POST | `/notifications` | notifications.* | NotificationController@index/readAll | notifications/index | auth | TV2 | Có |
| M10 | resource | `/admin/stocks` | admin.stocks.* | Admin\StockController (CRUD) | admin/stocks/* | auth, can:admin | TV4 | Có |
| M10 | resource | `/admin/users` | admin.users.* | Admin\UserController (CRUD, khóa/mở, tìm kiếm, phân trang) | admin/users/* | auth, can:admin | TV4 | Có |
| M10 | POST | `/admin/session/close` | admin.session.close | Admin\SettingController@closeSession | — | auth, can:admin | TV2 | Có |
| M10 | GET/PUT | `/admin/settings` | admin.settings.* | Admin\SettingController@edit/update | admin/settings/edit | auth, can:admin | TV4 | Có |
| M10 | POST | `/admin/prices/run` | admin.prices.run | Admin\SettingController@runPrices | — | auth, can:admin | TV4 | Có |
| M10 | GET | `/admin/audit-logs` | admin.audit.index | Admin\AuditLogController@index (lọc, phân trang) | admin/audit/index | auth, can:admin | TV4 | Có |
| M10 | GET/POST | `/admin/reconcile` | admin.reconcile.* | Admin\ReconcileController@index/store | admin/reconcile/index | auth, can:admin | TV1 | Có |

## Lệnh Artisan và lịch chạy

| Lệnh | Việc | Lịch | Phụ trách | Trạng thái |
|---|---|---|---|---|
| `tradesim:prices` | Price Engine sinh giá mới, khớp lệnh LO (`LimitOrderMatcher`) | Mỗi 30 giây khi phiên mở | TV2 | Có |
| `tradesim:close-session` | Chốt giá đóng cửa, đặt giá tham chiếu mới, hủy lệnh LO còn treo | 14:45 thứ Hai–Sáu | TV2 | Có |
| `tradesim:snapshot` | Chụp ảnh danh mục cuối ngày (chốt phiên cũng tự chụp) | 15:05 thứ Hai–Sáu | TV3 | Có |
| `tradesim:reconcile` | Đối soát ví, sổ cái, lệnh, holdings, BR-15 | 15:10 thứ Hai–Sáu và chạy tay | TV1 | Có |
| `queue:work --stop-when-empty` | Xử lý hàng đợi (cảnh báo giá) | Mỗi phút | TV2 | Có |
