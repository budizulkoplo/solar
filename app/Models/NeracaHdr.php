<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeracaHdr extends Model
{
    protected $table = 'neraca_hdr';
    protected $fillable = ['rincian', 'aktiva', 'pasiva'];
}