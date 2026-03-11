<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetMutation extends Model
{
    protected $table = 'asset_mutations';
    
    protected $fillable = [
        'asset_id',
        'tanggal',
        'jenis',
        'subjenis',
        'nilai',
        'pihak_terkait',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'idnota'
    ];
    
    protected $casts = [
        'tanggal' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'nilai' => 'decimal:2'
    ];
    
    // Relationships
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
    
    public function nota()
    {
        return $this->belongsTo(Nota::class, 'idnota');
    }
    
    // Helper method untuk jenis mutasi
    public function getJenisTextAttribute()
    {
        $types = [
            'perbaikan' => 'Perbaikan',
            'penambahan_nilai' => 'Penambahan Nilai',
            'penjualan' => 'Penjualan',
            'hibah' => 'Hibah',
            'hilang' => 'Hilang/Rusak',
            'penghapusan' => 'Penghapusan',
            'sewa' => 'Sewa Aset'
        ];
        
        return $types[$this->jenis] ?? $this->jenis;
    }
}
