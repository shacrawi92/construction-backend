<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ─── TENANT DB: estimation_templates + estimation_template_items ──────────────
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // e.g. "Residential", "Commercial"
            $table->boolean('is_global')->default(false); // shared across company
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('estimation_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimation_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_type_id')->constrained('work_types');
            $table->text('description')->nullable();
            $table->string('unit', 30);
            $table->decimal('default_qty', 12, 3)->default(0);
            $table->decimal('unit_price_etb', 18, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimation_template_items');
        Schema::dropIfExists('estimation_templates');
    }
};
