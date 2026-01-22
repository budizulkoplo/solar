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
        'idbarang',
        'description',
        'nominal',
        'jml',
        'total'
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

    // Relasi ke barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'idbarang', 'idbarang');
    }
}