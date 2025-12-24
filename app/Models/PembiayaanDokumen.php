<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembiayaanDokumen extends Model
{
    protected $table = 'pembiayaan_dokumen';
    
    protected $fillable = [
        'pembiayaan_id',
        'nama_file',
        'path_file',
        'tipe_file',
        'size_file',
        'created_by'
    ];
    
    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class, 'pembiayaan_id');
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}