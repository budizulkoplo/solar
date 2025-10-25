<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $table = 'jadwal';
    protected $primaryKey = 'idjadwal';
    public $timestamps = false;

    protected $fillable = [
        'tgl',
        'pegawai_nik',
        'shift'
    ];
}
