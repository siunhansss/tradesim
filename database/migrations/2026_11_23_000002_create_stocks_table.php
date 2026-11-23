<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique();
            $table->string('name');
            $table->string('exchange', 10)->default('HOSE');
            $table->string('industry', 100)->nullable();
            $table->text('description')->nullable();
            // Giá tính bằng VND, số nguyên đồng (BR-09, mục 6.2).
            $table->decimal('reference_price', 18, 0);
            $table->decimal('ceiling_price', 18, 0);
            $table->decimal('floor_price', 18, 0);
            $table->decimal('last_price', 18, 0);
            $table->unsignedBigInteger('volume')->default(0);
            $table->timestamp('last_price_at')->nullable();
            $table->string('price_source', 50)->default('Dữ liệu mẫu');
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('stock_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 18, 0);
            $table->unsignedBigInteger('volume')->default(0);
            $table->string('source', 50);
            $table->timestamp('recorded_at');
            $table->index(['stock_id', 'recorded_at']);
        });

        Schema::create('market_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_settings');
        Schema::dropIfExists('stock_prices');
        Schema::dropIfExists('stocks');
    }
};
