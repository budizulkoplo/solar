<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KelompokJam extends Model
{
    use HasFactory;

    protected $table = 'kelompokjam';
    protected $primaryKey = 'id';
    public $timestamps = false; // jika tidak ada created_at/updated_at

    protected $fillable = [
        'shift',
        'jammasuk',
        'jampulang',
    ];
}
