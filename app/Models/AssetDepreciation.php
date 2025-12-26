<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends Model
{
    protected $table = 'asset_depreciations';
    
    protected $fillable = [
        'asset_id',
        'periode',
        'bulan_ke',
        'nilai_penyusutan',
        'akumulasi_penyusutan',
        'nilai_buku',
        'status',
        'idnota',
        'keterangan'
    ];
    
    protected $casts = [
        'periode' => 'date',
        'nilai_penyusutan' => 'decimal:2',
        'akumulasi_penyusutan' => 'decimal:2',
        'nilai_buku' => 'decimal:2'
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
    
    // Scopes
    public function scopePosted($query)
    {
        return $query->where('status', 'terposting');
    }
    
    public function scopeForPeriod($query, $period)
    {
        return $query->where('periode', $period);
    }
}