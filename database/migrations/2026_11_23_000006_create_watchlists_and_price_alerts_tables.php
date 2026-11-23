<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'stock_id']);
        });

        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->string('condition', 3);            // gte | lte
            $table->decimal('target_price', 18, 0);
            $table->string('status', 20)->default('active');
            $table->timestamp('triggered_at')->nullable();
            $table->decimal('triggered_price', 18, 0)->nullable();
            $table->timestamps();
            $table->index(['stock_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_alerts');
        Schema::dropIfExists('watchlists');
    }
};
