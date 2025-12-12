<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Angsuran extends Model
{
    protected $table = 'angsuran';
    
    protected $fillable = [
        'idnota',
        'idrek',
        'tanggal',
        'jumlah',
        'keterangan'
    ];

    protected $dates = ['tanggal'];

    public $timestamps = true;

    /**
     * Relasi ke nota
     */
    public function nota()
    {
        return $this->belongsTo(Nota::class, 'idnota');
    }

    /**
     * Relasi ke rekening
     */
    public function rekening()
    {
        return $this->belongsTo(Rekening::class, 'idrek', 'idrek');
    }
}