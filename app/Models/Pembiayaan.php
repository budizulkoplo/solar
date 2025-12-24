<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembiayaan extends Model
{
    use SoftDeletes;
    
    protected $table = 'pembiayaan';
    protected $fillable = [
        'kode_pembiayaan',
        'judul',
        'jenis',
        'idcompany',
        'idproject',
        'rekening_id',
        'nominal',
        'tanggal',
        'deskripsi',
        'metode_pembayaran',
        'status',
        'created_by'
    ];
    
    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2'
    ];
    
    public function company()
    {
        return $this->belongsTo(CompanyUnit::class, 'idcompany');
    }
    
    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }
    
    public function rekening()
    {
        return $this->belongsTo(Rekening::class, 'rekening_id', 'idrek');
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function logs()
    {
        return $this->hasMany(PembiayaanLog::class, 'pembiayaan_id')->orderBy('created_at', 'desc');
    }
    
    public function dokumen()
    {
        return $this->hasMany(PembiayaanDokumen::class, 'pembiayaan_id');
    }
    
    // Scope untuk pembiayaan company
    public function scopeCompanyOnly($query)
    {
        return $query->where('jenis', 'company')->whereNull('idproject');
    }
    
    // Scope untuk pembiayaan project
    public function scopeProjectOnly($query)
    {
        return $query->where('jenis', 'project')->whereNotNull('idproject');
    }
}