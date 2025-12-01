<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaPayment extends Model
{
    use HasFactory;

    protected $table = 'nota_payments';
    protected $fillable = [
        'idnota',
        'idrek',
        'tanggal',
        'jumlah'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2'
    ];

    public function nota()
    {
        return $this->belongsTo(Nota::class, 'idnota');
    }

    public function rekening()
    {
        return $this->belongsTo(Rekening::class, 'idrek');
    }
}