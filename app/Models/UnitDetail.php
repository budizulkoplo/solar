<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitDetail extends Model
{
    protected $fillable = [
        'idunit',
        'status'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'idunit');
    }
}