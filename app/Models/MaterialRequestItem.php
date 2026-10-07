<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequestItem extends Model
{
    protected $fillable = [
        'material_request_id', 'material_id',
        'qty_requested', 'qty_approved', 'unit_cost_etb',
    ];

    protected $casts = [
        'qty_requested' => 'decimal:3',
        'qty_approved'  => 'decimal:3',
        'unit_cost_etb' => 'decimal:2',
    ];

    public function request()  { return $this->belongsTo(MaterialRequest::class, 'material_request_id'); }
    public function material() { return $this->belongsTo(Material::class); }

    public function getLineTotalAttribute(): float
    {
        return round((float)($this->qty_approved ?? 0) * (float)($this->unit_cost_etb ?? 0), 2);
    }
}
