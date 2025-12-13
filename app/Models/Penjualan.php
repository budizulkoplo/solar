<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penjualan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'kode_penjualan',
        'unit_detail_id',
        'customer_id',
        'booking_id',
        'harga_jual',
        'dp_awal',
        'sisa_pembayaran',
        'metode_pembayaran',
        'bank_kredit',
        'tenor_kredit',
        'cicilan_bulanan',
        'tanggal_akad',
        'tanggal_serah_terima',
        'nota_ppjb',
        'ajb',
        'keterangan',
        'status_penjualan',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'tanggal_akad' => 'date',
        'tanggal_serah_terima' => 'date',
        'harga_jual' => 'decimal:2',
        'dp_awal' => 'decimal:2',
        'sisa_pembayaran' => 'decimal:2',
        'cicilan_bulanan' => 'decimal:2',
        'tenor_kredit' => 'integer'
    ];

    // Relationship dengan UnitDetail
    public function unitDetail()
    {
        return $this->belongsTo(UnitDetail::class, 'unit_detail_id');
    }

    // Relationship dengan Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // Relationship dengan Booking
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    // Relationship dengan User (created_by)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship dengan User (updated_by)
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Generate kode penjualan otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($penjualan) {
            $penjualan->kode_penjualan = self::generateKodePenjualan();
        });
    }

    public static function generateKodePenjualan()
    {
        $prefix = 'PJL';
        $date = date('ym');
        $lastPenjualan = self::where('kode_penjualan', 'like', $prefix . $date . '%')
            ->orderBy('kode_penjualan', 'desc')
            ->first();

        if ($lastPenjualan) {
            $lastNumber = (int) substr($lastPenjualan->kode_penjualan, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . $newNumber;
    }
}