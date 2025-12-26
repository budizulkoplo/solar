<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $table = 'assets';
    
    protected $fillable = [
        'kode_aset',
        'nama_aset',
        'idkodetransaksi',
        'kodetransaksi',
        'tanggal_pembelian',
        'tanggal_mulai_susut',
        'harga_perolehan',
        'nilai_residu',
        'umur_ekonomis',
        'metode_penyusutan',
        'persentase_susut',
        'status',
        'lokasi',
        'pic',
        'keterangan',
        'idcompany',
        'idproject',
        'idretail',
        'idnota'
    ];
    
    protected $casts = [
        'tanggal_pembelian' => 'date',
        'tanggal_mulai_susut' => 'date',
        'harga_perolehan' => 'decimal:2',
        'nilai_residu' => 'decimal:2',
        'persentase_susut' => 'decimal:2'
    ];
    
    // Relationships
    public function kodeTransaksi()
    {
        return $this->belongsTo(KodeTransaksi::class, 'idkodetransaksi');
    }
    
    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }
    
    public function depreciations()
    {
        return $this->hasMany(AssetDepreciation::class, 'asset_id');
    }
    
    public function mutations()
    {
        return $this->hasMany(AssetMutation::class, 'asset_id');
    }
    
    public function nota()
    {
        return $this->belongsTo(Nota::class, 'idnota');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
    
    public function scopeForProject($query, $projectId)
    {
        return $query->where('idproject', $projectId);
    }
    
    // Hitung penyusutan per bulan
    public function calculateMonthlyDepreciation()
    {
        if ($this->metode_penyusutan === 'garis_lurus') {
            return ($this->harga_perolehan - $this->nilai_residu) / $this->umur_ekonomis;
        } 
        elseif ($this->metode_penyusutan === 'saldo_menurun') {
            // Saldo menurun dengan persentase tertentu
            $rate = $this->persentase_susut / 100 / 12; // per bulan
            return $this->nilai_buku * $rate;
        }
        
        return 0;
    }
    
    // Hitung nilai buku saat ini
    public function getNilaiBukuAttribute()
    {
        $totalDepreciation = $this->depreciations()
            ->where('status', 'terposting')
            ->sum('nilai_penyusutan');
            
        return $this->harga_perolehan - $totalDepreciation;
    }
    
    // Generate kode aset otomatis
    public static function generateKodeAset($projectCode = null)
    {
        $prefix = 'AST-' . ($projectCode ?: 'PRJ') . '-' . date('Ym');
        $lastAsset = self::where('kode_aset', 'like', $prefix . '%')
            ->orderBy('kode_aset', 'desc')
            ->first();
            
        if ($lastAsset) {
            $lastNumber = intval(substr($lastAsset->kode_aset, -4));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
        
        return $prefix . '-' . $nextNumber;
    }
}