<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitKerja extends Model
{
    use HasFactory;

    protected $table = 'company_units';
    protected $primaryKey = 'id';
    protected $fillable = ['company_name', 'lokasi', 'lokasi_lock'];
}
