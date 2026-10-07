<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetTransaction extends Model
{
    protected $fillable = [
        'budget_id', 'amount_etb', 'type', 'reference', 'note', 'recorded_by', 'transacted_at',
    ];

    protected $casts = [
        'amount_etb'    => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

    public function budget()   { return $this->belongsTo(Budget::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
