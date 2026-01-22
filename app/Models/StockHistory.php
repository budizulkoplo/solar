<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use HasFactory;

    protected $table = 'stock_history';
    
    protected $fillable = [
        'barang_id',
        'project_id',
        'tipe',
        'qty',
        'qty_sebelum',
        'qty_sesudah',
        'keterangan',
        'idnota',
        'created_by'
    ];

    // Relasi ke barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'idbarang');
    }

    // Relasi ke project
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    // Relasi ke nota
    public function nota()
    {
        return $this->belongsTo(Nota::class, 'idnota');
    }

    // Relasi ke user yang membuat
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}