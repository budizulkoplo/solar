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
        'jatuh_tempo',
        'suku_bunga',
        'biaya_administrasi',
        'deskripsi',
        'status',
        'created_by'
    ];

    protected $dates = ['tanggal', 'jatuh_tempo', 'deleted_at'];

    // Relationships
    public function rekening()
    {
        return $this->belongsTo(Rekening::class, 'rekening_id', 'idrek');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'idcompany');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dokumen()
    {
        return $this->hasMany(PembiayaanDokumen::class, 'pembiayaan_id');
    }

    public function setorans()
    {
        return $this->hasMany(PembiayaanSetoran::class, 'pembiayaan_id');
    }

    public function logs()
    {
        return $this->hasMany(PembiayaanLog::class, 'pembiayaan_id')->orderBy('created_at', 'desc');
    }

    // Scopes
    public function scopeCompanyOnly($query)
    {
        return $query->where('jenis', 'company');
    }

    public function scopeProjectOnly($query)
    {
        return $query->where('jenis', 'project');
    }

    // Calculate total setoran
    public function getTotalSetoranAttribute()
    {
        return $this->setorans()->where('status', 'paid')->sum('pokok');
    }

    // Calculate remaining
    public function getSisaAttribute()
    {
        return $this->nominal - $this->total_setoran;
    }

    // Check if lunas
    public function getIsLunasAttribute()
    {
        return $this->sisa <= 0;
    }

    // Status badge
    public function getStatusBadgeAttribute()
    {
        $badge = [
            'draft' => 'bg-secondary',
            'active' => 'bg-primary',
            'overdue' => 'bg-danger',
            'lunas' => 'bg-success'
        ];
        
        $statusText = [
            'draft' => 'Draft',
            'active' => 'Aktif',
            'overdue' => 'Jatuh Tempo',
            'lunas' => 'Lunas'
        ];
        
        return '<span class="badge ' . ($badge[$this->status] ?? 'bg-secondary') . '">' . 
               ($statusText[$this->status] ?? $this->status) . '</span>';
    }
}