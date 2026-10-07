<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkType extends Model
{
    protected $fillable = [
        'name_en', 'name_am', 'name_so',
        'category', 'unit', 'default_unit_rate_etb', 'is_active',
    ];

    protected $casts = [
        'default_unit_rate_etb' => 'decimal:2',
        'is_active'             => 'boolean',
    ];

    public function estimationItems()        { return $this->hasMany(EstimationItem::class); }
    public function estimationTemplateItems(){ return $this->hasMany(EstimationTemplateItem::class); }
    public function laborLogs()              { return $this->hasMany(LaborLog::class); }

    /** Return translated name based on locale. */
    public function getLocalizedName(string $locale = 'en'): string
    {
        return match($locale) {
            'am'    => $this->name_am ?? $this->name_en,
            'so'    => $this->name_so ?? $this->name_en,
            default => $this->name_en,
        };
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeByCategory($query, string $cat) { return $query->where('category', $cat); }
}
