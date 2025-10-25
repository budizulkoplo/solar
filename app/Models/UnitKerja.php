<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitKerja extends Model
{
    use HasFactory;

    protected $table = 'unitkerja';
    protected $primaryKey = 'id';
    protected $fillable = ['namaunit', 'lokasi', 'umk', 'lokasi_lock'];
}
