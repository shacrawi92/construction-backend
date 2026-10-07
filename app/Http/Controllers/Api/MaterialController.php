<?php

namespace App\Http\Controllers\Api;

use App\Domains\Inventory\RequestApprovalService;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\Project;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function __construct(private RequestApprovalService $approvalService) {}

    // ── Materials inventory ────────────────────────────────────────────────────

    public function index(Request $request)
    {
        return response()->json(
            Material::active()
                ->when($request->low_stock, fn ($q) => $q->lowStock())
                ->when($request->search,    fn ($q, $s) => $q->where('name_en', 'like', "%$s%"))
                ->orderBy('name_en')
                ->paginate(20)
        );
    }

    public function show(Material $material)
    {
        return response()->json($material->append('is_low_stock'));
    }

    // ── Material Requests ──────────────────────────────────────────────────────

    public function storeRequest(Request $request, Project $project)
    {
        $data = $request->validate([
            'reason'        => 'nullable|string',
            'items'         => 'required|array|min:1',
            'items.*.material_id'    => 'required|exists:materials,id',
            'items.*.qty_requested'  => 'required|numeric|min:0.001',
        ]);

        $matRequest = MaterialRequest::create([
            'project_id' => $project->id,
            'foreman_id' => $request->user()->id,
            'reason'     => $data['reason'] ?? null,
            'status'     => 'pending',
        ]);

        foreach ($data['items'] as $item) {
            $matRequest->items()->create([
                'material_id'   => $item['material_id'],
                'qty_requested' => $item['qty_requested'],
            ]);
        }

        return response()->json($matRequest->load('items.material'), 201);
    }

    public function requests(Project $project)
    {
        return response()->json(
            $project->materialRequests()->with(['foreman:id,name', 'items.material'])->latest()->get()
        );
    }

    public function approveRequest(Request $request, MaterialRequest $matRequest)
    {
        $data = $request->validate([
            'approvals'                                  => 'required|array',
            'approvals.*.material_request_item_id'       => 'required|integer',
            'approvals.*.qty_approved'                   => 'required|numeric|min:0',
        ]);

        $this->authorize('approve-material-requests');

        $updated = $this->approvalService->approve($matRequest, $request->user(), $data['approvals']);
        return response()->json($updated);
    }

    public function rejectRequest(Request $request, MaterialRequest $matRequest)
    {
        $request->validate(['note' => 'required|string|max:500']);
        $this->authorize('approve-material-requests');

        $updated = $this->approvalService->reject($matRequest, $request->user(), $request->note);
        return response()->json($updated);
    }
}
