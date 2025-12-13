<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'kode_booking',
        'unit_detail_id',
        'customer_id',
        'tanggal_booking',
        'dp_awal',
        'metode_pembayaran_dp',
        'bukti_pembayaran_dp',
        'tanggal_jatuh_tempo',
        'keterangan',
        'status_booking',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'tanggal_booking' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'dp_awal' => 'decimal:2'
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

    // Generate kode booking otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            $booking->kode_booking = self::generateKodeBooking();
        });
    }

    public static function generateKodeBooking()
    {
        $prefix = 'BKG';
        $date = date('ym');
        $lastBooking = self::where('kode_booking', 'like', $prefix . $date . '%')
            ->orderBy('kode_booking', 'desc')
            ->first();

        if ($lastBooking) {
            $lastNumber = (int) substr($lastBooking->kode_booking, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . $newNumber;
    }
}