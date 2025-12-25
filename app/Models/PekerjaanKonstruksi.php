<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PekerjaanKonstruksi extends Model
{
    use SoftDeletes;

    protected $table = 'pekerjaan_kontruksi'; // Note: typo in table name
    protected $fillable = [
        'idproject',
        'nama_pekerjaan',
        'jenis_pekerjaan',
        'lokasi',
        'volume',
        'satuan',
        'anggaran',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah',
        'status',
        'keterangan'
    ];

    protected $casts = [
        'volume' => 'decimal:2',
        'anggaran' => 'decimal:2',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'jumlah' => 'integer'
    ];

    // Relationship dengan Project
    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }

    // Scope untuk jenis pekerjaan
    public function scopeJenis($query, $jenis)
    {
        return $query->where('jenis_pekerjaan', $jenis);
    }

    // Scope untuk status
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk yang aktif (tidak dihapus dan tidak canceled)
    public function scopeAktif($query)
    {
        return $query->whereNull('deleted_at')
                    ->where('status', '!=', 'canceled');
    }

    // Method untuk cek status
    public function isPlanning()
    {
        return $this->status === 'planning';
    }

    public function isOngoing()
    {
        return $this->status === 'ongoing';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCanceled()
    {
        return $this->status === 'canceled';
    }

    // Method untuk mengubah status
    public function updateStatus($newStatus)
    {
        $this->status = $newStatus;
        return $this->save();
    }

    // Hitung progress berdasarkan tanggal
    public function getProgressAttribute()
    {
        if (!$this->tanggal_mulai || !$this->tanggal_selesai) {
            return 0;
        }

        $start = strtotime($this->tanggal_mulai);
        $end = strtotime($this->tanggal_selesai);
        $today = strtotime(date('Y-m-d'));

        if ($today <= $start) {
            return 0;
        } elseif ($today >= $end) {
            return 100;
        } else {
            $totalDays = $end - $start;
            $passedDays = $today - $start;
            return ($passedDays / $totalDays) * 100;
        }
    }
    
    public function getEstimatedTimeRemaining()
    {
        if (!$this->tanggal_mulai || !$this->tanggal_selesai) {
            return null;
        }
        
        $start = \Carbon\Carbon::parse($this->tanggal_mulai);
        $end = \Carbon\Carbon::parse($this->tanggal_selesai);
        $today = \Carbon\Carbon::now();
        
        if ($today->gte($end)) {
            return 'Selesai';
        }
        
        $daysRemaining = $today->diffInDays($end);
        
        if ($daysRemaining === 0) {
            return 'Hari ini';
        } elseif ($daysRemaining === 1) {
            return '1 hari lagi';
        } else {
            return $daysRemaining . ' hari lagi';
        }
    }
}