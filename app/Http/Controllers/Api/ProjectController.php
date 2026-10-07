<?php

namespace App\Http\Controllers\Api;

use App\Domains\Projects\ProjectService;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $service) {}

    public function index(Request $request)
    {
        $projects = Project::query()
            ->when($request->status, fn ($q, $s) => $q->forStatus($s))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%$s%"))
            ->with(['foreman:id,name', 'budgets'])
            ->withCount('estimations')
            ->latest()
            ->paginate(15);

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'location'            => 'required|string|max:255',
            'client_name'         => 'required|string|max:255',
            'client_phone'        => 'nullable|string|max:30',
            'client_email'        => 'nullable|email',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'contract_value'      => 'nullable|numeric|min:0',
            'assigned_foreman_id' => 'nullable|exists:users,id',
        ]);

        $project = $this->service->create($request->user(), $data);

        return response()->json($project->load('foreman', 'budgets'), 201);
    }

    public function show(Project $project)
    {
        return response()->json(
            $project->load(['foreman', 'estimations', 'budgets', 'timelines', 'materialRequests'])
                    ->append(['total_spent', 'total_allocated', 'overall_progress'])
        );
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'name'                => 'sometimes|string|max:255',
            'description'         => 'nullable|string',
            'location'            => 'sometimes|string|max:255',
            'client_name'         => 'sometimes|string|max:255',
            'client_phone'        => 'nullable|string|max:30',
            'client_email'        => 'nullable|email',
            'status'              => 'sometimes|in:draft,active,on_hold,completed,cancelled',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date',
            'contract_value'      => 'nullable|numeric|min:0',
            'assigned_foreman_id' => 'nullable|exists:users,id',
        ]);

        return response()->json($this->service->update($project, $data));
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Project archived']);
    }
}
