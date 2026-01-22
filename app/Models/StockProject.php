<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockProject extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stock_project';
    protected $primaryKey = 'barang_id';
    public $incrementing = false;
    
    protected $fillable = [
        'barang_id',
        'project_id',
        'stock'
    ];

    protected $dates = ['deleted_at'];

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
}