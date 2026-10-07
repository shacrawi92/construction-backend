<?php

namespace App\Domains\Finances;

use App\Models\Budget;
use App\Models\BudgetTransaction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /**
     * Allocate or update a budget category for a project.
     */
    public function allocate(Project $project, string $category, float $amount, ?string $notes = null): Budget
    {
        return Budget::updateOrCreate(
            ['project_id' => $project->id, 'category' => $category],
            ['allocated_etb' => $amount, 'notes' => $notes]
        );
    }

    /**
     * Record a debit (spend) transaction and update spent_etb.
     */
    public function recordDebit(Budget $budget, float $amount, User $recorder, string $reference = '', string $note = ''): BudgetTransaction
    {
        return DB::transaction(function () use ($budget, $amount, $recorder, $reference, $note) {
            $tx = BudgetTransaction::create([
                'budget_id'     => $budget->id,
                'amount_etb'    => $amount,
                'type'          => 'debit',
                'reference'     => $reference,
                'note'          => $note,
                'recorded_by'   => $recorder->id,
                'transacted_at' => now(),
            ]);
            $budget->increment('spent_etb', $amount);
            return $tx;
        });
    }

    /**
     * Record a credit (correction/refund) and reverse spent_etb.
     */
    public function recordCredit(Budget $budget, float $amount, User $recorder, string $note = ''): BudgetTransaction
    {
        return DB::transaction(function () use ($budget, $amount, $recorder, $note) {
            $tx = BudgetTransaction::create([
                'budget_id'     => $budget->id,
                'amount_etb'    => $amount,
                'type'          => 'credit',
                'note'          => $note,
                'recorded_by'   => $recorder->id,
                'transacted_at' => now(),
            ]);
            $budget->decrement('spent_etb', $amount);
            return $tx;
        });
    }

    /**
     * Burn rate: average daily spend over a rolling window (in ETB).
     */
    public function burnRate(Budget $budget, int $days = 30): float
    {
        $total = $budget->transactions()
            ->where('type', 'debit')
            ->where('transacted_at', '>=', now()->subDays($days))
            ->sum('amount_etb');

        return round((float)$total / $days, 2);
    }

    /**
     * Project-level cost report: estimated vs. actual per category.
     */
    public function costReport(Project $project): array
    {
        $budgets = $project->budgets()->with('transactions')->get();

        $totalEstimated = $project->estimations()
            ->where('status', 'approved')
            ->sum('total_etb');

        return [
            'total_estimated_etb' => $totalEstimated,
            'total_allocated_etb' => $budgets->sum('allocated_etb'),
            'total_spent_etb'     => $budgets->sum('spent_etb'),
            'categories'          => $budgets->map(fn (Budget $b) => [
                'category'       => $b->category,
                'allocated_etb'  => $b->allocated_etb,
                'spent_etb'      => $b->spent_etb,
                'remaining_etb'  => $b->remaining,
                'usage_pct'      => $b->usage_percent,
                'burn_rate_etb'  => $this->burnRate($b),
            ])->values(),
        ];
    }
}
