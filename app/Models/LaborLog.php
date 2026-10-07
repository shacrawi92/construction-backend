<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaborLog extends Model
{
    protected $fillable = [
        'project_id', 'foreman_id', 'work_type_id', 'worker_name',
        'log_date', 'hours_worked', 'daily_rate_etb', 'total_cost_etb', 'notes',
    ];

    protected $casts = [
        'log_date'       => 'date',
        'hours_worked'   => 'decimal:2',
        'daily_rate_etb' => 'decimal:2',
        'total_cost_etb' => 'decimal:2',
    ];

    public function project()  { return $this->belongsTo(Project::class); }
    public function foreman()  { return $this->belongsTo(User::class, 'foreman_id'); }
    public function workType() { return $this->belongsTo(WorkType::class); }

    protected static function booted(): void
    {
        static::saving(function (LaborLog $log) {
            // total = (hours / 8) * daily_rate
            $log->total_cost_etb = round(((float)$log->hours_worked / 8) * (float)$log->daily_rate_etb, 2);
        });
    }
}
