<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiHdr extends Model
{
    protected $table = 'transaksi_hdr';
    protected $fillable = ['keterangan'];
}