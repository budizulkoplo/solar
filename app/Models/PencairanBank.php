<?php
// app/Models/PencairanBank.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PencairanBank extends Model
{
    use SoftDeletes;
    
    protected $table = 'pencairan_bank';
    protected $fillable = [
        'kode_pencairan',
        'penjualan_id',
        'bank_kredit',
        'tanggal_pencairan',
        'nominal_pencairan',
        'jenis_pencairan',
        'termin_ke',
        'status_pencairan',
        'keterangan',
        'bukti_pencairan',
        'tanggal_realisasi',
        'no_rekening_bank',
        'nama_rekening',
        'created_by'
    ];
    
    protected $casts = [
        'nominal_pencairan' => 'decimal:2',
        'tanggal_pencairan' => 'date',
        'tanggal_realisasi' => 'date'
    ];
    
    // Relasi dengan penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }
    
    // Relasi dengan user yang membuat
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    // Scope untuk total pencairan per penjualan
    public function scopeTotalByPenjualan($query, $penjualanId)
    {
        return $query->where('penjualan_id', $penjualanId)
            ->where('status_pencairan', 'realized')
            ->sum('nominal_pencairan');
    }
    
    // Scope untuk pencairan yang sudah direalisasi
    public function scopeRealized($query)
    {
        return $query->where('status_pencairan', 'realized');
    }
}