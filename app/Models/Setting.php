<?php
// app/Models/Setting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'setting';
     protected $fillable = [
        'nama_perusahaan',
        'alamat',
        'telepon',
        'path_logo',
    ];
    public $timestamps = false;
}
