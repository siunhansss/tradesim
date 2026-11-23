<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Tổng số dư (gồm cả tiền đang giữ) và tiền đang giữ cho lệnh mua chờ.
            // Số dư khả dụng = balance - reserved, không bao giờ âm (BR-02).
            $table->decimal('balance', 18, 0)->default(0);
            $table->decimal('reserved', 18, 0)->default(0);
            $table->timestamps();
        });

        // Sổ biến động tiền: chỉ ghi thêm, không sửa, không xóa (BR-14).
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 18, 0);
            $table->decimal('balance_after', 18, 0);
            $table->decimal('reserved_after', 18, 0);
            $table->nullableMorphs('reference');
            // Khóa chống ghi trùng, ví dụ "INIT:15" cho vốn khởi tạo (BR-01, BR-08).
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['wallet_id', 'created_at']);
            $table->index(['wallet_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
