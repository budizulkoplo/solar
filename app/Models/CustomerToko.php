<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerToko extends Model
{
    use SoftDeletes;

    protected $table = 'customer_toko';

    protected $fillable = [
        'kode_customer',
        'nama_lengkap',
        'no_hp',
        'alamat',
        'keterangan',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->kode_customer)) {
                $customer->kode_customer = self::generateKodeCustomer();
            }
        });
    }

    public static function generateKodeCustomer(): string
    {
        $prefix = 'CT';
        $date = now()->format('ym');

        $lastCustomer = self::where('kode_customer', 'like', $prefix . $date . '%')
            ->orderByDesc('kode_customer')
            ->first();

        $nextNumber = $lastCustomer
            ? ((int) substr($lastCustomer->kode_customer, -4)) + 1
            : 1;

        return $prefix . $date . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
