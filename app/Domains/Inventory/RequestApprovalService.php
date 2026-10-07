<?php

namespace App\Domains\Inventory;

use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\User;
use App\Notifications\MaterialRequestReviewed;
use Illuminate\Support\Facades\DB;

class RequestApprovalService
{
    /**
     * Approve a material request, deducting stock and updating spent budgets.
     *
     * @param  array<int, array{material_request_item_id: int, qty_approved: float}> $approvals
     */
    public function approve(MaterialRequest $request, User $reviewer, array $approvals): MaterialRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $approvals) {
            foreach ($approvals as $approval) {
                $item = MaterialRequestItem::findOrFail($approval['material_request_item_id']);
                $item->update([
                    'qty_approved'  => $approval['qty_approved'],
                    'unit_cost_etb' => $item->material->unit_cost_etb,
                ]);

                // Deduct from stock
                $item->material->decrement('stock_qty', $approval['qty_approved']);

                // Flag low stock
                if ($item->material->fresh()->is_low_stock) {
                    logger()->warning("Low stock: {$item->material->name_en}", ['material_id' => $item->material_id]);
                }
            }

            $allApproved = $request->items()->whereNull('qty_approved')->doesntExist();

            $request->update([
                'status'      => $allApproved ? 'approved' : 'partial',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            // Real-time notification to foreman
            $request->foreman->notify(new MaterialRequestReviewed($request));

            return $request->fresh('items.material');
        });
    }

    public function reject(MaterialRequest $request, User $reviewer, string $note): MaterialRequest
    {
        $request->update([
            'status'         => 'rejected',
            'rejection_note' => $note,
            'reviewed_by'    => $reviewer->id,
            'reviewed_at'    => now(),
        ]);

        $request->foreman->notify(new MaterialRequestReviewed($request));

        return $request;
    }
}
