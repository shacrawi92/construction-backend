<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ─── TENANT DB: estimations + estimation_items ────────────────────────────────
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('estimation_templates')->nullOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->decimal('subtotal_etb', 18, 2)->default(0);
            $table->decimal('contingency_pct', 5, 2)->default(0); // e.g. 5.00 = 5%
            $table->decimal('contingency_etb', 18, 2)->default(0);
            $table->decimal('total_etb', 18, 2)->default(0);
            $table->enum('status', ['draft', 'approved', 'revised'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('estimation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimation_id')->constrained('estimations')->cascadeOnDelete();
            $table->foreignId('work_type_id')->nullable()->constrained('work_types')->nullOnDelete();
            $table->string('description');
            $table->string('unit', 30);
            $table->decimal('qty', 12, 3)->default(0);
            $table->decimal('unit_price_etb', 18, 2)->default(0);
            $table->decimal('total_etb', 18, 2)->default(0); // computed: qty * unit_price
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimation_items');
        Schema::dropIfExists('estimations');
    }
};
