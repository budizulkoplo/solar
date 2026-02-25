<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanPayment extends Model
{
    use HasFactory;

    protected $table = 'penjualan_payments';
    
    protected $fillable = [
        'kode_payment',
        'penjualan_id',
        'jenis_payment',
        'termin_ke',
        'tanggal_payment',
        'nominal',
        'metode_pembayaran',
        'idrek',
        'bank',
        'no_rekening',
        'nama_rekening',
        'status_payment',
        'keterangan',
        'bukti_payment',
        'created_by'
    ];

    protected $casts = [
        'tanggal_payment' => 'date',
        'nominal' => 'decimal:2',
        'termin_ke' => 'integer'
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rekening()
    {
        return $this->belongsTo(Rekening::class, 'idrek', 'idrek');
    }
}