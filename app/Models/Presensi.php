<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $table = 'presensi';
    protected $primaryKey = 'id';
    public $timestamps = true; // karena ada created_at & updated_at

    protected $fillable = [
        'nik',
        'tgl_presensi',
        'jam_in',
        'inoutmode',
        'foto_in',
        'lokasi'
    ];
}
