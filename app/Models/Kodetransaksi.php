<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kodetransaksi extends Model
{
    protected $table = 'kodetransaksi';
    protected $fillable = ['kodetransaksi','transaksi','idcoa'];

    public function coa()
    {
        return $this->belongsTo(Coa::class, 'idcoa');
    }
}
