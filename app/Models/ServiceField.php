<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceField extends Model
{
    protected $fillable = [
        'service_id', 'field_key', 'label', 'unit',
        'input_type', 'normal_text', 'is_required', 'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}