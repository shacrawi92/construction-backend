<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimationTemplateItem extends Model
{
    protected $fillable = [
        'estimation_template_id', 'work_type_id', 'description',
        'unit', 'default_qty', 'unit_price_etb', 'sort_order',
    ];

    protected $casts = [
        'default_qty'   => 'decimal:3',
        'unit_price_etb'=> 'decimal:2',
    ];

    public function template() { return $this->belongsTo(EstimationTemplate::class, 'estimation_template_id'); }
    public function workType() { return $this->belongsTo(WorkType::class); }
}
