<?php

namespace App\Domains\Projects;

use App\Models\Estimation;
use App\Models\EstimationItem;
use App\Models\EstimationTemplate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EstimationService
{
    /**
     * Create a blank estimation for a project.
     */
    public function createBlank(Project $project, User $creator, array $data): Estimation
    {
        return DB::transaction(function () use ($project, $creator, $data) {
            return Estimation::create([
                'project_id'      => $project->id,
                'template_id'     => null,
                'title'           => $data['title'],
                'notes'           => $data['notes'] ?? null,
                'contingency_pct' => $data['contingency_pct'] ?? 0,
                'status'          => 'draft',
                'created_by'      => $creator->id,
            ]);
        });
    }

    /**
     * Create an estimation by cloning a template.
     */
    public function createFromTemplate(Project $project, User $creator, EstimationTemplate $template, array $overrides = []): Estimation
    {
        return DB::transaction(function () use ($project, $creator, $template, $overrides) {
            $estimation = Estimation::create([
                'project_id'      => $project->id,
                'template_id'     => $template->id,
                'title'           => $overrides['title'] ?? $template->name,
                'notes'           => $overrides['notes'] ?? $template->description,
                'contingency_pct' => $overrides['contingency_pct'] ?? 0,
                'status'          => 'draft',
                'created_by'      => $creator->id,
            ]);

            foreach ($template->items as $i => $templateItem) {
                EstimationItem::create([
                    'estimation_id'  => $estimation->id,
                    'work_type_id'   => $templateItem->work_type_id,
                    'description'    => $templateItem->description,
                    'unit'           => $templateItem->unit,
                    'qty'            => $templateItem->default_qty,
                    'unit_price_etb' => $templateItem->unit_price_etb,
                    'total_etb'      => round((float)$templateItem->default_qty * (float)$templateItem->unit_price_etb, 2),
                    'sort_order'     => $i,
                ]);
            }

            $estimation->recalculate();
            return $estimation->fresh('items');
        });
    }

    /**
     * Save an estimation as a reusable template.
     */
    public function saveAsTemplate(Estimation $estimation, User $creator, string $name): EstimationTemplate
    {
        return DB::transaction(function () use ($estimation, $creator, $name) {
            $template = EstimationTemplate::create([
                'name'        => $name,
                'description' => $estimation->notes,
                'created_by'  => $creator->id,
            ]);

            foreach ($estimation->items as $i => $item) {
                $template->items()->create([
                    'work_type_id'   => $item->work_type_id,
                    'description'    => $item->description,
                    'unit'           => $item->unit,
                    'default_qty'    => $item->qty,
                    'unit_price_etb' => $item->unit_price_etb,
                    'sort_order'     => $i,
                ]);
            }

            return $template;
        });
    }

    /**
     * Upsert a line item and trigger parent recalculation.
     */
    public function upsertItem(Estimation $estimation, array $data): EstimationItem
    {
        $item = $estimation->items()->updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'work_type_id'   => $data['work_type_id'] ?? null,
                'description'    => $data['description'],
                'unit'           => $data['unit'],
                'qty'            => $data['qty'],
                'unit_price_etb' => $data['unit_price_etb'],
                'total_etb'      => round((float)$data['qty'] * (float)$data['unit_price_etb'], 2),
                'sort_order'     => $data['sort_order'] ?? 0,
            ]
        );
        $estimation->recalculate();
        return $item;
    }

    public function approve(Estimation $estimation, User $approver): Estimation
    {
        $estimation->update([
            'status'      => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
        return $estimation;
    }
}
