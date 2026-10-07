<?php

namespace App\Http\Controllers\Api;

use App\Domains\Projects\EstimationService;
use App\Http\Controllers\Controller;
use App\Models\Estimation;
use App\Models\EstimationTemplate;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EstimationController extends Controller
{
    public function __construct(private EstimationService $service) {}

    public function index(Project $project)
    {
        return response()->json(
            $project->estimations()->with(['items.workType', 'creator:id,name'])->latest()->get()
        );
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'notes'           => 'nullable|string',
            'contingency_pct' => 'nullable|numeric|min:0|max:100',
            'template_id'     => 'nullable|exists:estimation_templates,id',
        ]);

        if (!empty($data['template_id'])) {
            $template   = EstimationTemplate::findOrFail($data['template_id']);
            $estimation = $this->service->createFromTemplate($project, $request->user(), $template, $data);
        } else {
            $estimation = $this->service->createBlank($project, $request->user(), $data);
        }

        return response()->json($estimation->load('items.workType'), 201);
    }

    public function show(Estimation $estimation)
    {
        return response()->json($estimation->load(['items.workType', 'project:id,name,code', 'creator:id,name']));
    }

    public function upsertItem(Request $request, Estimation $estimation)
    {
        $data = $request->validate([
            'id'             => 'nullable|integer',
            'work_type_id'   => 'nullable|exists:work_types,id',
            'description'    => 'required|string|max:500',
            'unit'           => 'required|string|max:30',
            'qty'            => 'required|numeric|min:0',
            'unit_price_etb' => 'required|numeric|min:0',
            'sort_order'     => 'nullable|integer',
        ]);

        $item = $this->service->upsertItem($estimation, $data);
        return response()->json($item->fresh('workType'));
    }

    public function deleteItem(Estimation $estimation, int $itemId)
    {
        $estimation->items()->findOrFail($itemId)->delete();
        $estimation->recalculate();
        return response()->json(['message' => 'Item deleted']);
    }

    public function approve(Request $request, Estimation $estimation)
    {
        return response()->json($this->service->approve($estimation, $request->user()));
    }

    public function saveAsTemplate(Request $request, Estimation $estimation)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $template = $this->service->saveAsTemplate($estimation, $request->user(), $request->name);
        return response()->json($template->load('items'), 201);
    }

    public function exportPdf(Estimation $estimation)
    {
        $estimation->load(['items.workType', 'project', 'creator']);
        $pdf = Pdf::loadView('exports.estimation-pdf', compact('estimation'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("estimation-{$estimation->id}.pdf");
    }

    public function exportExcel(Estimation $estimation)
    {
        return Excel::download(
            new \App\Exports\EstimationExport($estimation),
            "estimation-{$estimation->id}.xlsx"
        );
    }
}
