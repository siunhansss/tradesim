<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('cash', 18, 0);
            $table->decimal('stock_value', 18, 0);
            $table->decimal('total_assets', 18, 0);
            $table->decimal('realized_pnl', 18, 0);
            $table->decimal('unrealized_pnl', 18, 0);
            $table->timestamps();
            // Mỗi tài khoản một ảnh chụp mỗi ngày (BR-16).
            $table->unique(['user_id', 'snapshot_date']);
        });

        Schema::create('portfolio_snapshot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_snapshot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('price', 18, 0);
            $table->decimal('value', 18, 0);
            $table->decimal('weight', 7, 4);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshot_items');
        Schema::dropIfExists('portfolio_snapshots');
    }
};
