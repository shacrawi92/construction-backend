<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $fillable = [
        'project_id', 'foreman_id', 'status', 'reason',
        'rejection_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function project()  { return $this->belongsTo(Project::class); }
    public function foreman()  { return $this->belongsTo(User::class, 'foreman_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function items()    { return $this->hasMany(MaterialRequestItem::class); }

    public function isPending():  bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return in_array($this->status, ['approved', 'partial']); }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    public function getTotalCostEtbAttribute(): float
    {
        return (float) $this->items()->sum(\DB::raw('qty_approved * unit_cost_etb'));
    }
}
