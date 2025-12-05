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

    public function coa()
    {
        return $this->belongsTo(Coa::class, 'idcoa');
    }

    public function transaksiHeader()
    {
        return $this->belongsTo(TransaksiHdr::class, 'idheader');
    }

    public function neracaHeader()
    {
        return $this->belongsTo(NeracaHdr::class, 'idneraca');
    }

    public function labaRugiHeader()
    {
        return $this->belongsTo(LabaRugiHdr::class, 'idlabarugi');
    }
}