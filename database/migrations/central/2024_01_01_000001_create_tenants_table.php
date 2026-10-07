<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ─── CENTRAL DB MIGRATION: tenants ───────────────────────────────────────────
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. "acme-construction"
            $table->string('name');
            $table->string('plan')->default('starter'); // starter|pro|enterprise
            $table->boolean('is_active')->default(true);
            $table->json('data')->nullable(); // extra tenant metadata
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
