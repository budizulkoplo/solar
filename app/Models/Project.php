<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_project',
        'lokasi',
        'keterangan',
    ];

    public function transaksiArmada()
    {
        return $this->hasMany(\App\Models\TransaksiArmada::class, 'project_id');
    }
}
