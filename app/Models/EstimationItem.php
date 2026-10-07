<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimationItem extends Model
{
    protected $fillable = [
        'estimation_id', 'work_type_id', 'description',
        'unit', 'qty', 'unit_price_etb', 'total_etb', 'sort_order',
    ];

    protected $casts = [
        'qty'           => 'decimal:3',
        'unit_price_etb'=> 'decimal:2',
        'total_etb'     => 'decimal:2',
    ];

    public function estimation() { return $this->belongsTo(Estimation::class); }
    public function workType()   { return $this->belongsTo(WorkType::class); }

    protected static function booted(): void
    {
        $recalc = fn (EstimationItem $item) => $item->estimation?->recalculate();

        static::saved($recalc);
        static::deleted($recalc);
    }

    public function computeTotal(): void
    {
        $this->total_etb = round((float)$this->qty * (float)$this->unit_price_etb, 2);
    }
}
