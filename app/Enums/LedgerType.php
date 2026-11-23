<?php

namespace App\Enums;

/**
 * Loại bút toán trong sổ biến động tiền (wallet_transactions).
 *
 * Quy ước dấu của amount:
 * - init, buy, sell, fee, tax, adjust: ảnh hưởng tới tổng số dư (balance).
 * - hold, release: ảnh hưởng tới số dư khả dụng; hold âm (bị giữ lại), release dương (được trả lại).
 */
enum LedgerType: string
{
    case Init = 'init';         // vốn khởi tạo
    case Hold = 'hold';         // giữ tiền cho lệnh mua chờ
    case Release = 'release';   // giải phóng tiền giữ
    case Buy = 'buy';           // tiền mua cổ phiếu
    case Sell = 'sell';         // tiền bán cổ phiếu
    case Fee = 'fee';           // phí giao dịch
    case Tax = 'tax';           // thuế bán mô phỏng
    case Adjust = 'adjust';     // điều chỉnh có kiểm toán

    public function label(): string
    {
        return match ($this) {
            self::Init => 'Vốn khởi tạo',
            self::Hold => 'Giữ tiền',
            self::Release => 'Giải phóng tiền giữ',
            self::Buy => 'Mua',
            self::Sell => 'Bán',
            self::Fee => 'Phí giao dịch',
            self::Tax => 'Thuế bán',
            self::Adjust => 'Điều chỉnh',
        };
    }

    /** Bút toán giữ/giải phóng chỉ đổi tiền giữ, không đổi tổng số dư. */
    public function affectsReservedOnly(): bool
    {
        return $this === self::Hold || $this === self::Release;
    }
}
