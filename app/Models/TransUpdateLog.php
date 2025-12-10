<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransUpdateLog extends Model
{
    protected $table = 'trans_update_log';
    
    protected $fillable = [
        'idnota',
        'nota_no',
        'update_log'
    ];
    
    // Jika ingin relasi ke Nota
    public function nota()
    {
        return $this->belongsTo(Nota::class, 'idnota', 'id');
    }
}