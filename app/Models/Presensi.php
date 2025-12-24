<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $table = 'presensi';
    protected $primaryKey = 'id';
    public $timestamps = true; // karena ada created_at & updated_at

    protected $fillable = [
        'nik',
        'tgl_presensi',
        'jam_in',
        'inoutmode',
        'foto_in',
        'lokasi'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope untuk filter berdasarkan user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('tanggal', $date);
    }

    /**
     * Scope untuk filter berdasarkan bulan
     */
    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('tanggal', $year)
                    ->whereMonth('tanggal', $month);
    }
}
