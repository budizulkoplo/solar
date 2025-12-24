<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiPindahBukuLog extends Model
{
    protected $table = 'transaksi_pindah_buku_logs';
    
    protected $fillable = [
        'pindah_buku_id',
        'log_type',
        'description',
        'created_by'
    ];
    
    public function transaksi()
    {
        return $this->belongsTo(TransaksiPindahBuku::class, 'pindah_buku_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}