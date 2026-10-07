<?php

namespace App\Domains\Personnel;

use App\Models\LaborLog;
use App\Models\Project;
use App\Models\User;

class LaborLogService
{
    public function record(Project $project, User $foreman, array $data): LaborLog
    {
        // total_cost_etb auto-calculated in LaborLog::booted()
        return LaborLog::create([
            'project_id'     => $project->id,
            'foreman_id'     => $foreman->id,
            'work_type_id'   => $data['work_type_id'] ?? null,
            'worker_name'    => $data['worker_name'],
            'log_date'       => $data['log_date'],
            'hours_worked'   => $data['hours_worked'] ?? 8,
            'daily_rate_etb' => $data['daily_rate_etb'],
            'notes'          => $data['notes'] ?? null,
        ]);
    }

    /**
     * Daily labour cost per project for a date range.
     *
     * @return array<string, float>  date => total_cost_etb
     */
    public function dailyCostSeries(Project $project, string $from, string $to): array
    {
        return LaborLog::where('project_id', $project->id)
            ->whereBetween('log_date', [$from, $to])
            ->selectRaw('log_date, SUM(total_cost_etb) as daily_total')
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->pluck('daily_total', 'log_date')
            ->toArray();
    }

    public function totalCost(Project $project): float
    {
        return (float) LaborLog::where('project_id', $project->id)->sum('total_cost_etb');
    }
}
