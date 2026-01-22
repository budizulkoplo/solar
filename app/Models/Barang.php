<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang';
    protected $primaryKey = 'idbarang';
    public $incrementing = true;
    
    protected $fillable = [
        'nama_barang',
        'harga_beli',
        'harga_jual',
        'deskripsi'
    ];

    protected $dates = ['deleted_at'];

    // Relasi ke stock project
    public function stockProjects()
    {
        return $this->hasMany(StockProject::class, 'barang_id', 'idbarang');
    }

    // Relasi ke stock history
    public function stockHistory()
    {
        return $this->hasMany(StockHistory::class, 'barang_id', 'idbarang');
    }

    // Relasi ke nota transactions
    public function notaTransactions()
    {
        return $this->hasMany(NotaTransaction::class, 'idbarang', 'idbarang');
    }

    // Scope untuk pencarian
    public function scopeSearch($query, $search)
    {
        return $query->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
    }
}