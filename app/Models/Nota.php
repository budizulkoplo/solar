<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    protected $fillable = [
        'nota_no', 'namatransaksi', 'idproject', 'idcompany', 'idretail',
        'vendor_id', 'idrek', 'tanggal', 'cashflow', 'paymen_method',
        'tgl_tempo', 'subtotal', 'ppn', 'diskon', 'total', 'status',
        'bukti_nota', 'nip', 'namauser','type','unit_detail_id','pekerjaan_konstruksi_id'
    ];

    // Add relationship to update logs
    public function updateLogs()
    {
        return $this->hasMany(TransUpdateLog::class, 'idnota');
    }

    // Keep other relationships as they were...
    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }

    // Relasi ke company_units (PT)
    public function companyUnit()
    {
        return $this->belongsTo(CompanyUnit::class, 'idcompany');
    }


    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function rekening()
    {
        return $this->belongsTo(Rekening::class, 'idrek', 'idrek');
    }

    public function transactions()
    {
        return $this->hasMany(NotaTransaction::class, 'idnota');
    }

    public function payments()
    {
        return $this->hasMany(NotaPayment::class, 'idnota');
    }

    public function cashflows()
    {
        return $this->hasMany(Cashflow::class, 'idnota');
    }

    public function angsuran()
    {
        return $this->hasMany(Angsuran::class, 'idnota');
    }

    public function unitDetail()
    {
        return $this->belongsTo(UnitDetail::class, 'unit_detail_id');
    }

    public function pekerjaanKonstruksi()
    {
        return $this->belongsTo(PekerjaanKonstruksi::class, 'pekerjaan_konstruksi_id');
    }

    public function scopeKonstruksi($query)
    {
        return $query->where('type', 'konstruksi');
    }

    public function scopeByPekerjaan($query, $pekerjaanId)
    {
        return $query->where('pekerjaan_konstruksi_id', $pekerjaanId);
    }
}