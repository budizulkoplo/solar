<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nota_transactions';
    protected $fillable = [
        'idnota',
        'idkodetransaksi',
        'description',
        'nominal',
        'jml',
        'total'
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'jml' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    // Relasi ke nota
    public function nota()
    {
        return $this->belongsTo(Nota::class, 'idnota');
    }

    // Relasi ke kode transaksi
    public function kodeTransaksi()
    {
        return $this->belongsTo(KodeTransaksi::class, 'idkodetransaksi');
    }
}