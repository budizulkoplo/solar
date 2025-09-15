<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    protected $table = 'rekening';
    protected $primaryKey = 'idrek';
    public $timestamps = false;

    protected $fillable = [
        'norek', 'namarek', 'saldo', 'saldoakhir', 'idproject', 'idcompany'
    ];

    public function company()
    {
        return $this->belongsTo(CompanyUnit::class, 'idcompany');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }
}
