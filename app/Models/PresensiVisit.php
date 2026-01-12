<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiVisit extends Model
{
    protected $table = 'presensi_visit';
    protected $fillable = [
        'nik',
        'tgl_presensi',
        'jam_in',
        'inoutmode',
        'foto_in',
        'lokasi',
        'keterangan'
    ];

    protected $casts = [
        'tgl_presensi' => 'date',
        'jam_in' => 'datetime:H:i:s',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'nik', 'nik');
    }
}