<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembiayaanLog extends Model
{
    protected $table = 'pembiayaan_logs';
    
    protected $fillable = [
        'pembiayaan_id',
        'log_type',
        'description',
        'created_by'
    ];
    
    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class, 'pembiayaan_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}