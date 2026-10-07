<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    protected $fillable = [
        'project_id', 'phase_name', 'phase_name_am', 'phase_name_so',
        'planned_start', 'planned_end', 'actual_start', 'actual_end',
        'progress_pct', 'status', 'sort_order',
    ];

    protected $casts = [
        'planned_start' => 'date',
        'planned_end'   => 'date',
        'actual_start'  => 'date',
        'actual_end'    => 'date',
        'progress_pct'  => 'integer',
    ];

    public function project() { return $this->belongsTo(Project::class); }

    public function getLocalizedPhase(string $locale = 'en'): string
    {
        return match($locale) {
            'am'    => $this->phase_name_am ?? $this->phase_name,
            'so'    => $this->phase_name_so ?? $this->phase_name,
            default => $this->phase_name,
        };
    }

    public function getIsDelayedAttribute(): bool
    {
        return $this->status === 'delayed'
            || ($this->planned_end && now()->toDateString() > $this->planned_end->toDateString()
                && $this->progress_pct < 100);
    }
}
