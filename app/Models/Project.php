<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'idcompany',
        'idretail',
        'namaproject',
        'lokasi',
        'luas',
        'deskripsi',
        'logo',   // tambahkan logo di sini
    ];

    public function company()
    {
        return $this->belongsTo(CompanyUnit::class, 'idcompany');
    }

    public function companyUnit()
    {
        return $this->belongsTo(\App\Models\CompanyUnit::class, 'idcompany');
    }

    public function retail()
    {
        return $this->belongsTo(Retail::class, 'idretail');
    }
    
    public function units()
    {
        return $this->hasMany(\App\Models\Unit::class, 'idproject');
    }

}
