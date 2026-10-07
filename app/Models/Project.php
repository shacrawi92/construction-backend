<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description', 'location',
        'client_name', 'client_phone', 'client_email',
        'status', 'start_date', 'end_date', 'contract_value',
        'created_by', 'assigned_foreman_id', 'meta',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'contract_value' => 'decimal:2',
        'meta'           => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function creator()      { return $this->belongsTo(User::class, 'created_by'); }
    public function foreman()      { return $this->belongsTo(User::class, 'assigned_foreman_id'); }
    public function estimations()  { return $this->hasMany(Estimation::class); }
    public function budgets()      { return $this->hasMany(Budget::class); }
    public function timelines()    { return $this->hasMany(Timeline::class)->orderBy('sort_order'); }
    public function laborLogs()    { return $this->hasMany(LaborLog::class); }
    public function materialRequests() { return $this->hasMany(MaterialRequest::class); }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)    { return $query->where('status', 'active'); }
    public function scopeForStatus($query, string $status) { return $query->where('status', $status); }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getTotalSpentAttribute(): float
    {
        return (float) $this->budgets()->sum('spent_etb');
    }

    public function getTotalAllocatedAttribute(): float
    {
        return (float) $this->budgets()->sum('allocated_etb');
    }

    public function getOverallProgressAttribute(): int
    {
        $timelines = $this->timelines;
        if ($timelines->isEmpty()) return 0;
        return (int) $timelines->avg('progress_pct');
    }

    // ── Lifecycle ──────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->code)) {
                $count = static::withTrashed()->count() + 1;
                $project->code = 'PRJ-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
