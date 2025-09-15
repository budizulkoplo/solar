<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisUnit extends Model
{
    protected $table = 'jenisunit';
    protected $fillable = ['jenisunit'];

    public function units()
    {
        return $this->hasMany(Unit::class, 'idjenis');
    }
}
