<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    use HasFactory;

    protected $table = 'coa'; 

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'level',
    ];

    public function parent()
    {
        return $this->belongsTo(Coa::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Coa::class, 'parent_id');
    }
}
