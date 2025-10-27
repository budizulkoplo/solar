<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterGaji extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mastergaji';
    protected $primaryKey = 'idgaji';

    protected $fillable = [
        'tgl_aktif',
        'nik',
        'gajipokok',
        'masakerja',
        'komunikasi',
        'transportasi',
        'konsumsi',
        'tunj_asuransi',
        'jabatan',
        'asuransi',
        'verifikasi',
    ];

    public function pegawai()
    {
        return $this->belongsTo(User::class, 'nik', 'nik');
    }
}
