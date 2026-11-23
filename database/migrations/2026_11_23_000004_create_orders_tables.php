<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->restrictOnDelete();
            $table->string('side', 4);                 // buy | sell
            $table->string('type', 2);                 // MP | LO
            $table->unsignedInteger('quantity');
            $table->decimal('limit_price', 18, 0)->nullable();
            $table->unsignedInteger('filled_quantity')->default(0);
            $table->decimal('avg_fill_price', 18, 4)->nullable();
            // Tài sản còn giữ cho phần chưa khớp (gộp order_reservations vào đây).
            $table->decimal('reserved_amount', 18, 0)->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->string('status', 20);
            $table->string('reject_reason')->nullable();
            // Mã yêu cầu do form sinh ra; gửi lại cùng mã không tạo lệnh mới (BR-08).
            $table->string('request_key', 64);
            $table->timestamps();

            $table->unique(['user_id', 'request_key']);
            $table->index(['user_id', 'status']);
            $table->index(['stock_id', 'status']);
        });

        // Mỗi lần khớp là một trade; không sửa, không xóa (BR-06).
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->restrictOnDelete();
            $table->string('side', 4);
            $table->unsignedInteger('quantity');
            $table->decimal('price', 18, 0);
            $table->decimal('value', 18, 0);
            $table->decimal('fee', 18, 0);
            $table->decimal('tax', 18, 0)->default(0);
            // Lưu tỷ lệ áp dụng tại thời điểm khớp (BR-13).
            $table->decimal('fee_rate', 8, 6);
            $table->decimal('tax_rate', 8, 6)->default(0);
            $table->decimal('realized_pnl', 18, 0)->nullable();
            $table->timestamp('executed_at');
            $table->index(['user_id', 'executed_at']);
        });

        Schema::create('holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            // Tổng giá vốn còn lại (đã gồm phí mua); giá vốn bình quân = total_cost / quantity.
            $table->decimal('total_cost', 18, 0)->default(0);
            $table->decimal('realized_pnl', 18, 0)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'stock_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holdings');
        Schema::dropIfExists('trades');
        Schema::dropIfExists('orders');
    }
};
