<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ─── TENANT DB: work_types ────────────────────────────────────────────────────
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_am')->nullable(); // Amharic
            $table->string('name_so')->nullable(); // Somali
            $table->enum('category', ['civil', 'electrical', 'plumbing', 'finishing', 'mechanical', 'siteworks', 'other']);
            $table->string('unit', 30); // m², m³, m, kg, pcs, hr, etc.
            $table->decimal('default_unit_rate_etb', 18, 2)->default(0); // ETB
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_types');
    }
};
