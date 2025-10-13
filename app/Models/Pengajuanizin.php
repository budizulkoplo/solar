<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengajuanizin extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_izin'; // nama tabel

    protected $primaryKey = 'id'; // primary key
    public $timestamps = false; // kalau tidak ada created_at & updated_at

    protected $fillable = [
        'nik',
        'tgl_izin',
        'status',          // i:izin, s:sakit, c:cuti
        'keterangan',
        'status_approved', // 0:pending,1:approved,2:declined
    ];
}
