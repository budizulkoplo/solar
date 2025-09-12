<?php
// app/Models/CompanyUnit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyUnit extends Model
{
    protected $fillable = [
        'company_name','siup','npwp','alamat','logo'
    ];

    public function projects()
    {
        return $this->hasMany(Project::class, 'idcompany');
    }
}
