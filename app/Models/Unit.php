<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $table = 'units';
    protected $fillable = [
        'idproject',
        'namaunit',
        'tipe',
        'idjenis',
        'blok',
        'luastanah',
        'luasbangunan',
        'hargadasar',
        'jumlah',
    ];

    // Relasi ke jenisunit
    public function jenisUnit()
    {
        return $this->belongsTo(JenisUnit::class, 'idjenis');
    }

    // Relasi ke projects
    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }

    // Relasi ke unit_details
    public function details()
    {
        return $this->hasMany(UnitDetail::class, 'idunit');
    }

    public function unitDetails()
    {
        return $this->hasMany(UnitDetail::class, 'idunit');
    }

}
