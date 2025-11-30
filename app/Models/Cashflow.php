<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashflow extends Model
{
    use HasFactory;

    protected $table = 'cashflows';
    protected $fillable = [
        'idrek',
        'idnota',
        'tanggal',
        'cashflow',
        'nominal',
        'saldo_awal',
        'saldo_akhir',
        'keterangan'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'saldo_awal' => 'decimal:2',
        'saldo_akhir' => 'decimal:2'
    ];

    public function rekening()
    {
        return $this->belongsTo(Rekening::class, 'idrek');
    }

    public function nota()
    {
        return $this->belongsTo(Nota::class, 'idnota');
    }
}