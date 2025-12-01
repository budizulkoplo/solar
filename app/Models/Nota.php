<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nota extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notas';
    protected $fillable = [
        'nota_no',
        'idproject',
        'idcompany',
        'idretail',
        'idrek',
        'vendor_id',
        'tanggal',
        'cashflow',
        'paymen_method',
        'tgl_tempo',
        'total',
        'status',
        'bukti_nota',
        'nip',
        'namauser'
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'tgl_tempo' => 'date:Y-m-d',
        'total' => 'decimal:2'
    ];

    // Relasi ke project
    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }

    // Relasi ke company_units (PT)
    public function companyUnit()
    {
        return $this->belongsTo(CompanyUnit::class, 'idcompany');
    }

    // Relasi ke vendor
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    // Relasi detail transaksi
    public function transactions()
    {
        return $this->hasMany(NotaTransaction::class, 'idnota');
    }

    // Relasi pembayaran
    public function payments()
    {
        return $this->hasMany(NotaPayment::class, 'idnota');
    }

    // Relasi cashflow
    public function cashflows()
    {
        return $this->hasMany(Cashflow::class, 'idnota');
    }
}