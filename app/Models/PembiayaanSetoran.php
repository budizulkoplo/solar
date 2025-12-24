<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PembiayaanSetoran extends Model
{
    use SoftDeletes;

    protected $table = 'pembiayaan_setoran';
    protected $fillable = [
        'pembiayaan_id',
        'kode_setoran',
        'tanggal',
        'pokok',
        'administrasi',
        'margin',
        'total',
        'deskripsi',
        'bukti_path',
        'status',
        'created_by'
    ];

    protected $dates = ['tanggal', 'deleted_at'];

    // Relationships
    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class, 'pembiayaan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope untuk setoran yang sudah dibayar
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Scope untuk setoran aktif
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['paid', 'pending']);
    }
}