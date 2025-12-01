<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyUnit extends Model
{
    use SoftDeletes;

    protected $table = 'company_units';
    
    protected $fillable = [
        'company_name',
        'siup',
        'npwp',
        'alamat',
        'logo',
        'lokasi',
        'lokasi_lock'
    ];

    // Relasi ke rekening (rekening milik PT ini)
    public function rekenings()
    {
        return $this->hasMany(Rekening::class, 'idcompany')
            ->whereNull('idproject'); // Hanya rekening PT, bukan project
    }

    // Relasi ke nota (transaksi PT ini)
    public function notas()
    {
        return $this->hasMany(Nota::class, 'idcompany')
            ->whereNull('idproject'); // Hanya transaksi PT
    }

    // Relasi ke project (project yang dimiliki PT ini)
    public function projects()
    {
        return $this->hasMany(Project::class, 'idcompany');
    }
}