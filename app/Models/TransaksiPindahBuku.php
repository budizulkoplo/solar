<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiPindahBuku extends Model
{
    use SoftDeletes;
    
    protected $table = 'transaksi_pindah_buku';
    protected $fillable = [
        'kode_transaksi',
        'rekening_asal_id',
        'rekening_tujuan_id',
        'nominal',
        'keterangan',
        'tanggal',
        'status',
        'idcompany','idproject',
        'created_by'
    ];
    
    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2'
    ];
    
    public function rekeningAsal()
    {
        return $this->belongsTo(Rekening::class, 'rekening_asal_id', 'idrek');
    }
    
    public function rekeningTujuan()
    {
        return $this->belongsTo(Rekening::class, 'rekening_tujuan_id', 'idrek');
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function logs()
    {
        return $this->hasMany(TransaksiPindahBukuLog::class, 'pindah_buku_id');
    }
}