<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = ['project_id', 'category', 'allocated_etb', 'spent_etb', 'notes'];

    protected $casts = [
        'allocated_etb' => 'decimal:2',
        'spent_etb'     => 'decimal:2',
    ];

    public function project()      { return $this->belongsTo(Project::class); }
    public function transactions() { return $this->hasMany(BudgetTransaction::class)->latest('transacted_at'); }

    public function getRemainingAttribute(): float
    {
        return (float)$this->allocated_etb - (float)$this->spent_etb;
    }

    public function getUsagePercentAttribute(): float
    {
        if ((float)$this->allocated_etb === 0.0) return 0;
        return round(((float)$this->spent_etb / (float)$this->allocated_etb) * 100, 1);
    }
}
