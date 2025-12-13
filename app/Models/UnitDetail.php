<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'idunit',
        'status',
        'customer_id',
        'booking_id',
        'penjualan_id'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    // Relationship dengan Unit
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'idunit');
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

    // Relationship dengan Penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    // Scope untuk filter status
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk yang tersedia
    public function scopeTersedia($query)
    {
        return $query->where('status', 'tersedia');
    }

    // Scope untuk yang booking
    public function scopeBooking($query)
    {
        return $query->where('status', 'booking_unit');
    }

    // Scope untuk yang terjual
    public function scopeTerjual($query)
    {
        return $query->where('status', 'terjual');
    }

    // Method untuk cek apakah unit tersedia
    public function isTersedia()
    {
        return $this->status === 'tersedia';
    }

    // Method untuk cek apakah unit booking
    public function isBooking()
    {
        return $this->status === 'booking_unit';
    }

    // Method untuk cek apakah unit terjual
    public function isTerjual()
    {
        return $this->status === 'terjual';
    }

    // Method untuk mengubah status
    public function changeStatus($newStatus)
    {
        $this->status = $newStatus;
        return $this->save();
    }
}