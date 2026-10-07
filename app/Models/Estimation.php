<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'template_id', 'title', 'notes',
        'subtotal_etb', 'contingency_pct', 'contingency_etb', 'total_etb',
        'status', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'subtotal_etb'    => 'decimal:2',
        'contingency_pct' => 'decimal:2',
        'contingency_etb' => 'decimal:2',
        'total_etb'       => 'decimal:2',
        'approved_at'     => 'datetime',
    ];

    public function project()  { return $this->belongsTo(Project::class); }
    public function template() { return $this->belongsTo(EstimationTemplate::class); }
    public function items()    { return $this->hasMany(EstimationItem::class)->orderBy('sort_order'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }

    /** Recalculate subtotal, contingency, and total from line items. */
    public function recalculate(): void
    {
        $subtotal = $this->items()->sum('total_etb');
        $contingency = round($subtotal * ($this->contingency_pct / 100), 2);
        $this->update([
            'subtotal_etb'    => $subtotal,
            'contingency_etb' => $contingency,
            'total_etb'       => $subtotal + $contingency,
        ]);
    }
}
