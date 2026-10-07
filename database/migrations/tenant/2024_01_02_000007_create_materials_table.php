<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ─── TENANT DB: materials + material_requests + material_request_items ─────────
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_am')->nullable();
            $table->string('name_so')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->string('unit', 30); // kg, m², pcs, litre, bag, etc.
            $table->decimal('stock_qty', 12, 3)->default(0);
            $table->decimal('reorder_level', 12, 3)->default(0);
            $table->decimal('unit_cost_etb', 18, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('foreman_id')->constrained('users');
            $table->enum('status', ['pending', 'approved', 'partial', 'rejected'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('rejection_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('material_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_request_id')->constrained('material_requests')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('qty_requested', 12, 3);
            $table->decimal('qty_approved', 12, 3)->nullable();
            $table->decimal('unit_cost_etb', 18, 2)->nullable(); // cost at time of approval
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_requests');
        Schema::dropIfExists('materials');
    }
};
