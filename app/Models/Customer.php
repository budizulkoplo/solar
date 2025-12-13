<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'kode_customer',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'nik',
        'no_kk',
        'alamat_ktp',
        'rt_rw_ktp',
        'kelurahan_ktp',
        'kecamatan_ktp',
        'kota_ktp',
        'provinsi_ktp',
        'kode_pos_ktp',
        'alamat_domisili',
        'rt_rw_domisili',
        'kelurahan_domisili',
        'kecamatan_domisili',
        'kota_domisili',
        'provinsi_domisili',
        'kode_pos_domisili',
        'no_hp',
        'no_telp',
        'email',
        'pekerjaan',
        'nama_perusahaan',
        'alamat_perusahaan',
        'no_telp_perusahaan',
        'penghasilan_bulanan',
        'nama_ibu_kandung',
        'status_pernikahan',
        'nama_pasangan',
        'nik_pasangan',
        'nama_keluarga_dekat',
        'no_hp_keluarga_dekat',
        'hubungan_keluarga_dekat',
        'nama_bank',
        'no_rekening',
        'atas_nama_rekening',
        'npwp',
        'foto_ktp',
        'foto_kk',
        'foto_npwp',
        'foto_diri',
        'keterangan',
        'status_verifikasi'
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'penghasilan_bulanan' => 'decimal:2'
    ];

    // Generate kode customer otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            $customer->kode_customer = self::generateKodeCustomer();
        });
    }

    public static function generateKodeCustomer()
    {
        $prefix = 'CUST';
        $date = date('ym');
        $lastCustomer = self::where('kode_customer', 'like', $prefix . $date . '%')
            ->orderBy('kode_customer', 'desc')
            ->first();

        if ($lastCustomer) {
            $lastNumber = (int) substr($lastCustomer->kode_customer, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . $newNumber;
    }

    // Relationships
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function penjualans()
    {
        return $this->hasMany(Penjualan::class);
    }
}