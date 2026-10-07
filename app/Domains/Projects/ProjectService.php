<?php

namespace App\Domains\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function create(User $creator, array $data): Project
    {
        return DB::transaction(function () use ($creator, $data) {
            $project = Project::create([...$data, 'created_by' => $creator->id]);

            // Seed default budget categories
            $categories = ['Labour', 'Materials', 'Equipment', 'Overhead', 'Contingency'];
            foreach ($categories as $cat) {
                $project->budgets()->create(['category' => $cat, 'allocated_etb' => 0, 'spent_etb' => 0]);
            }

            return $project;
        });
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project->fresh();
    }

    public function archive(Project $project): Project
    {
        $project->update(['status' => 'completed']);
        return $project;
    }

    public function assignForeman(Project $project, User $foreman): Project
    {
        $project->update(['assigned_foreman_id' => $foreman->id]);
        $foreman->foreman()?->update(['current_project_id' => $project->id]);
        return $project->fresh('foreman');
    }

    public function cloneEstimation(Project $source, Project $target, User $creator): void
    {
        foreach ($source->estimations()->with('items')->get() as $estimation) {
            $clone = $target->estimations()->create([
                'title'           => $estimation->title . ' (Copy)',
                'notes'           => $estimation->notes,
                'contingency_pct' => $estimation->contingency_pct,
                'status'          => 'draft',
                'created_by'      => $creator->id,
            ]);
            foreach ($estimation->items as $item) {
                $clone->items()->create($item->only([
                    'work_type_id', 'description', 'unit', 'qty', 'unit_price_etb', 'total_etb', 'sort_order',
                ]));
            }
            $clone->recalculate();
        }
    }
}
