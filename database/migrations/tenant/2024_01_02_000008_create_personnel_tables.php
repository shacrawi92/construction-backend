<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ─── TENANT DB: foremen + timelines + labor_logs ──────────────────────────────
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foremen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('specialty')->nullable(); // e.g. "Structural", "Finishing"
            $table->date('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('phase_name');
            $table->string('phase_name_am')->nullable();
            $table->string('phase_name_so')->nullable();
            $table->date('planned_start');
            $table->date('planned_end');
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->unsignedTinyInteger('progress_pct')->default(0); // 0–100
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'delayed'])->default('not_started');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('labor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('foreman_id')->constrained('users');
            $table->foreignId('work_type_id')->nullable()->constrained('work_types')->nullOnDelete();
            $table->string('worker_name');
            $table->date('log_date');
            $table->decimal('hours_worked', 5, 2)->default(8);
            $table->decimal('daily_rate_etb', 18, 2)->default(0);
            $table->decimal('total_cost_etb', 18, 2)->default(0); // hours * rate / 8
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_logs');
        Schema::dropIfExists('timelines');
        Schema::dropIfExists('foremen');
    }
};
