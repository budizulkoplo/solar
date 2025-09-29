<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'rekening_id','nota_id','amount','saldo_akhir','keterangan'
    ];

    public function rekening()
    {
        return $this->belongsTo(Rekening::class, 'rekening_id');
    }

    public function nota()
    {
        return $this->belongsTo(Nota::class, 'nota_id');
    }
}
