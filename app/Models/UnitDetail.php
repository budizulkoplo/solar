<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitDetail extends Model
{
    use HasFactory;

    protected $table = 'unit_details';
    protected $fillable = ['idunit','status'];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'idunit');
    }
}
