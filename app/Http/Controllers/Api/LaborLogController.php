<?php

namespace App\Http\Controllers\Api;

use App\Domains\Personnel\LaborLogService;
use App\Http\Controllers\Controller;
use App\Models\LaborLog;
use App\Models\Project;
use Illuminate\Http\Request;

class LaborLogController extends Controller
{
    public function __construct(private LaborLogService $service) {}

    public function index(Request $request, Project $project)
    {
        return response()->json(
            $project->laborLogs()
                ->with(['foreman:id,name', 'workType:id,name_en,name_am,name_so'])
                ->when($request->date, fn ($q, $d) => $q->whereDate('log_date', $d))
                ->latest('log_date')
                ->paginate(20)
        );
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'worker_name'    => 'required|string|max:255',
            'work_type_id'   => 'nullable|exists:work_types,id',
            'log_date'       => 'required|date',
            'hours_worked'   => 'nullable|numeric|min:0.5|max:24',
            'daily_rate_etb' => 'required|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        $log = $this->service->record($project, $request->user(), $data);
        return response()->json($log->load('workType'), 201);
    }

    public function destroy(Project $project, LaborLog $laborLog)
    {
        abort_if($laborLog->project_id !== $project->id, 404);
        $laborLog->delete();
        return response()->json(['message' => 'Log deleted']);
    }

    public function dailyCostSeries(Request $request, Project $project)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);
        return response()->json($this->service->dailyCostSeries($project, $request->from, $request->to));
    }
}
