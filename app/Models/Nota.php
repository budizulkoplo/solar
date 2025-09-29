<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas';

    protected $fillable = [
        'nota_no','idproject','idcompany','idretail','tanggal','jenis','total','status'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'nota_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'idcompany');
    }
}
