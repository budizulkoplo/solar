<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bonus extends Model
{
    use SoftDeletes;

    protected $table = 'bonus';
    protected $fillable = [
        'periode',
        'nik',
        'nama',
        'nominal',
        'keterangan'
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'nik', 'nik');
    }
}