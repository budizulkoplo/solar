<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use SoftDeletes;

    protected $table = 'payroll';

    protected $fillable = [
        'periode',
        'nik',
        'nama',
        'jmlabsen',
        'lembur',
        'terlambat',
        'cuti',
        'gajipokok',
        'pek_tambahan',
        'masakerja',
        'komunikasi',
        'transportasi',
        'konsumsi',
        'tunj_asuransi',
        'jabatan',
        'cicilan',
        'asuransi',
        'zakat',
        'created_at',
        'updated_at',
    ];

    public function user()
        {
            return $this->belongsTo(User::class, 'nik', 'nik');
        }
}
