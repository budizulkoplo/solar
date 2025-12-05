<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kodetransaksi extends Model
{
    protected $table = 'kodetransaksi';
    protected $fillable = [
        'kodetransaksi',
        'transaksi',
        'idheader',
        'idcoa',
        'idneraca',
        'idlabarugi'
    ];

    // Relasi ke Coa
    public function coa()
    {
        return $this->belongsTo(Coa::class, 'idcoa');
    }

    // Relasi ke TransaksiHdr (header transaksi)
    public function header()
    {
        return $this->belongsTo(TransaksiHdr::class, 'idheader');
    }

    // Relasi ke NeracaHdr
    public function neraca()
    {
        return $this->belongsTo(NeracaHdr::class, 'idneraca');
    }

    // Relasi ke LabaRugiHdr
    public function labarugi()
    {
        return $this->belongsTo(LabaRugiHdr::class, 'idlabarugi');
    }
}