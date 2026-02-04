<?php
// app/Models/PenjualanPayment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenjualanPayment extends Model
{
    use SoftDeletes;
    
    protected $table = 'penjualan_payments';
    protected $fillable = [
        'kode_payment',
        'penjualan_id',
        'jenis_payment',
        'termin_ke',
        'tanggal_payment',
        'nominal',
        'metode_pembayaran',
        'bank',
        'no_rekening',
        'nama_rekening',
        'status_payment',
        'keterangan',
        'bukti_payment',
        'created_by'
    ];
    
    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal_payment' => 'date'
    ];
    
    // Relasi dengan penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }
    
    // Relasi dengan user yang membuat
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}