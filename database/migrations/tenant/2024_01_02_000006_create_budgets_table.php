<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ─── TENANT DB: budgets + budget_transactions ─────────────────────────────────
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('category'); // e.g. "Labour", "Materials", "Equipment", "Overhead"
            $table->decimal('allocated_etb', 18, 2)->default(0);
            $table->decimal('spent_etb', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'category']);
        });

        Schema::create('budget_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->decimal('amount_etb', 18, 2);
            $table->enum('type', ['debit', 'credit']); // debit = spend, credit = adjustment
            $table->string('reference')->nullable(); // e.g. invoice number
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamp('transacted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_transactions');
        Schema::dropIfExists('budgets');
    }
};
