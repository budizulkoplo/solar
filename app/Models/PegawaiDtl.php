<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PegawaiDtl extends Model
{
    use HasFactory;

    protected $table = 'pegawai_dtl';

    protected $fillable = [
        'nik',
        'awal_kontrak',
        'akhir_kontrak',
        'lokasi',
        'kota_user',
        'nama',
        'no_jkn_kis',
        'no_kpj',
        'sudah_dikirim',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat_ktp',
        'kode_pos',
        'jenis_kelamin',
        'gol_darah',
        'status_perkawinan',
        'jumlah_anak',
        'nama_ibu_kandung',
        'no_hp',
        'email_aktif',
        'pendidikan_terakhir'
    ];
}
