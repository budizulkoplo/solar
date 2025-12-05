<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabaRugiHdr extends Model
{
    protected $table = 'labarugi_hdr';
    protected $fillable = ['rincian', 'cashflow', 'kode_pemasukan', 'kode_pengeluaran'];
}