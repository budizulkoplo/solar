<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeracaAdjustment extends Model
{
    protected $table = 'neraca_adjustments';

    protected $fillable = [
        'module',
        'scope_id',
        'start_date',
        'end_date',
        'side',
        'row_key',
        'label',
        'value',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'value' => 'decimal:2',
    ];
}
