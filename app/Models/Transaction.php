<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'nota_id','coa_id','amount','keterangan'
    ];

    public function nota()
    {
        return $this->belongsTo(Nota::class, 'nota_id');
    }

    public function coa()
    {
        return $this->belongsTo(Coa::class, 'coa_id');
    }
}
