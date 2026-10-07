<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name_en', 'name_am', 'name_so', 'sku', 'unit',
        'stock_qty', 'reorder_level', 'unit_cost_etb', 'supplier', 'is_active',
    ];

    protected $casts = [
        'stock_qty'     => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'unit_cost_etb' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function requestItems() { return $this->hasMany(MaterialRequestItem::class); }

    public function getIsLowStockAttribute(): bool
    {
        return (float)$this->stock_qty <= (float)$this->reorder_level;
    }

    public function getLocalizedName(string $locale = 'en'): string
    {
        return match($locale) {
            'am'    => $this->name_am ?? $this->name_en,
            'so'    => $this->name_so ?? $this->name_en,
            default => $this->name_en,
        };
    }

    public function scopeActive($query)   { return $query->where('is_active', true); }
    public function scopeLowStock($query) { return $query->whereColumn('stock_qty', '<=', 'reorder_level'); }
}
