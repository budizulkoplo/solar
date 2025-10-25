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
        'gaji',
        'tunjangan',
        'nominallembur',
        'hln',
        'bpjs_kes',
        'bpjs_tk',
        'kasbon',
        'sisakasbon',
    ];
}
