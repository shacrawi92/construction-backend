<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EstimationController;
use App\Http\Controllers\Api\LaborLogController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  (tenant-scoped via stancl/tenancy middleware)
|--------------------------------------------------------------------------
*/

// ── Public Auth ────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',  [AuthController::class, 'login']);
});

// ── Sanctum-protected ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',         [AuthController::class, 'logout']);
        Route::get('/me',              [AuthController::class, 'me']);
        Route::patch('/locale',        [AuthController::class, 'updateLocale']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/',               fn (Request $r) => response()->json($r->user()->notifications()->paginate(20)));
        Route::patch('/{id}/read',    fn (Request $r, $id) => response()->json($r->user()->notifications()->findOrFail($id)->tap(fn($n) => $n->markAsRead())));
        Route::post('/read-all',      fn (Request $r) => response()->json($r->user()->unreadNotifications->markAsRead()));
    });

    // Work Types (lookup)
    Route::get('/work-types', fn () => response()->json(\App\Models\WorkType::active()->orderBy('category')->get()));

    // Estimation Templates (lookup + manage)
    Route::prefix('estimation-templates')->group(function () {
        Route::get('/',         fn () => response()->json(\App\Models\EstimationTemplate::with('items')->latest()->get()));
        Route::get('/{template}', fn (\App\Models\EstimationTemplate $template) => response()->json($template->load('items.workType')));
        Route::delete('/{template}', fn (\App\Models\EstimationTemplate $template) => response()->json($template->delete()));
    });

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Estimations (nested under project)
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/estimations',          [EstimationController::class, 'index']);
        Route::post('/estimations',         [EstimationController::class, 'store']);

        // Timelines
        Route::get('/timelines',  fn (\App\Models\Project $project) => response()->json($project->timelines));
        Route::post('/timelines', function (Request $r, \App\Models\Project $project) {
            $data = $r->validate([
                'phase_name'    => 'required|string',
                'phase_name_am' => 'nullable|string',
                'phase_name_so' => 'nullable|string',
                'planned_start' => 'required|date',
                'planned_end'   => 'required|date',
                'sort_order'    => 'nullable|integer',
            ]);
            return response()->json($project->timelines()->create($data), 201);
        });
        Route::patch('/timelines/{timeline}', function (Request $r, \App\Models\Project $project, \App\Models\Timeline $timeline) {
            $data = $r->validate([
                'progress_pct' => 'nullable|integer|min:0|max:100',
                'status'       => 'nullable|in:not_started,in_progress,completed,delayed',
                'actual_start' => 'nullable|date',
                'actual_end'   => 'nullable|date',
            ]);
            return response()->json($timeline->update($data) ? $timeline->fresh() : abort(422));
        });

        // Budget
        Route::get('/budgets',  fn (\App\Models\Project $project) => response()->json($project->budgets()->with('transactions')->get()));
        Route::post('/budgets/{budget}/transactions', function (Request $r, \App\Models\Project $project, \App\Models\Budget $budget) {
            $data = $r->validate([
                'amount_etb' => 'required|numeric|min:0.01',
                'type'       => 'required|in:debit,credit',
                'reference'  => 'nullable|string',
                'note'       => 'nullable|string',
            ]);
            return response()->json(app(\App\Domains\Finances\BudgetService::class)->{"record" . ucfirst($data['type'])}($budget, $data['amount_etb'], $r->user(), $data['reference'] ?? '', $data['note'] ?? ''), 201);
        });
        Route::get('/cost-report', fn (\App\Models\Project $project) => response()->json(app(\App\Domains\Finances\BudgetService::class)->costReport($project)));

        // Material requests
        Route::post('/material-requests',   [MaterialController::class, 'storeRequest']);
        Route::get('/material-requests',    [MaterialController::class, 'requests']);

        // Labor logs
        Route::get('/labor-logs',           [LaborLogController::class, 'index']);
        Route::post('/labor-logs',          [LaborLogController::class, 'store']);
        Route::delete('/labor-logs/{laborLog}', [LaborLogController::class, 'destroy']);
        Route::get('/labor-logs/daily-cost',[LaborLogController::class, 'dailyCostSeries']);
    });

    // Estimations standalone actions
    Route::prefix('estimations/{estimation}')->group(function () {
        Route::get('/',                [EstimationController::class, 'show']);
        Route::post('/items',          [EstimationController::class, 'upsertItem']);
        Route::delete('/items/{itemId}', [EstimationController::class, 'deleteItem']);
        Route::post('/approve',        [EstimationController::class, 'approve']);
        Route::post('/save-as-template', [EstimationController::class, 'saveAsTemplate']);
        Route::get('/export/pdf',      [EstimationController::class, 'exportPdf']);
        Route::get('/export/excel',    [EstimationController::class, 'exportExcel']);
    });

    // Materials inventory
    Route::get('/materials',          [MaterialController::class, 'index']);
    Route::get('/materials/{material}',[MaterialController::class, 'show']);

    // Material request review (admin/foreman with permission)
    Route::post('/material-requests/{matRequest}/approve', [MaterialController::class, 'approveRequest']);
    Route::post('/material-requests/{matRequest}/reject',  [MaterialController::class, 'rejectRequest']);
});
