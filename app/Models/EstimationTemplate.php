<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstimationTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'category', 'is_global', 'created_by'];

    protected $casts = ['is_global' => 'boolean'];

    public function items()   { return $this->hasMany(EstimationTemplateItem::class)->orderBy('sort_order'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function estimations() { return $this->hasMany(Estimation::class, 'template_id'); }
}
