<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nhật ký kiểm toán; không lưu mật khẩu, token (mục 8.2).
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->nullableMorphs('auditable');
            $table->string('result', 20)->default('ok');   // ok | denied | failed | skipped
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['action', 'created_at']);
        });

        Schema::create('job_runs', function (Blueprint $table) {
            $table->id();
            $table->string('job', 100);
            $table->string('status', 20);                  // running | ok | failed | skipped
            $table->json('details')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->index(['job', 'started_at']);
        });

        // Bảng chuẩn của Laravel Notifications (kênh database).
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('job_runs');
        Schema::dropIfExists('audit_logs');
    }
};
