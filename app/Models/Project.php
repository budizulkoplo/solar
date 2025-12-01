<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects'; // Pastikan nama tabel sesuai

    protected $fillable = [
        'idcompany',
        'idretail',
        'namaproject',
        'lokasi',
        'luas',
        'deskripsi',
        'logo',
    ];


    /**
     * Relasi ke company unit
     */
    public function company()
    {
        return $this->belongsTo(CompanyUnit::class, 'idcompany');
    }

    /**
     * Relasi ke company unit (alias)
     */
    public function companyUnit()
    {
        return $this->belongsTo(CompanyUnit::class, 'idcompany');
    }

    /**
     * Relasi ke retail
     */
    public function retail()
    {
        return $this->belongsTo(Retail::class, 'idretail');
    }
    
    /**
     * Relasi ke units
     */
    public function units()
    {
        return $this->hasMany(Unit::class, 'idproject');
    }

    /**
     * Accessor untuk idcompany dengan fallback yang aman
     */
    public function getIdCompanyAttribute()
    {
        // Akses property langsung, Eloquent sudah handle jika tidak ada
        return $this->attributes['idcompany'] ?? session('active_project_company_id') ?? null;
    }

    /**
     * Accessor untuk nama project
     */
    public function getNamaProjectAttribute()
    {
        return $this->attributes['namaproject'] ?? null;
    }

    /**
     * Method helper untuk mendapatkan idcompany dengan fallback
     * (Untuk digunakan di controller)
     */
    public function getCompanyId()
    {
        // Cek langsung di attributes array untuk menghindari infinite loop
        if (array_key_exists('idcompany', $this->attributes) && $this->attributes['idcompany'] !== null) {
            return $this->attributes['idcompany'];
        }
        
        // Fallback ke session atau default
        return session('active_project_company_id') ?? 1;
    }

    /**
     * Scope untuk project aktif
     */


    /**
     * Scope untuk project dengan company tertentu
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('idcompany', $companyId);
    }

    /**
     * Cek apakah project memiliki company
     */
    public function hasCompany()
    {
        return !empty($this->attributes['idcompany']);
    }

    /**
     * Boot method untuk model
     */

}