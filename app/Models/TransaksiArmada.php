<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiArmada extends Model
{
    use HasFactory;

    protected $table = 'transaksi_armada';

    protected $fillable = [
        'armada_id',
        'no_struk',
        'user_id',
        'project_id',
        'tgl_transaksi',
        'panjang',
        'lebar',
        'tinggi',
        'plus',
        'volume',
    ];

    public function armada()
    {
        return $this->belongsTo(Armada::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    protected $casts = [
        'tgl_transaksi' => 'datetime',
    ];

}
