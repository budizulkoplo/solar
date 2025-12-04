<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    protected $table = 'rekening';
    protected $primaryKey = 'idrek';
    public $timestamps = false;

    protected $fillable = [
        'norek', 'namarek', 'saldo', 'saldoawal', 'idproject', 'idcompany'
    ];

    public function company()
    {
        return $this->belongsTo(CompanyUnit::class, 'idcompany');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('idcompany', $companyId)
            ->whereNull('idproject');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'idproject');
    }

    public function scopeForProject($query, $projectId)
    {
        $project = \App\Models\Project::find($projectId);

        if (!$project) {
            return $query->whereRaw('1 = 0'); // project tidak ditemukan, kosong
        }

        return $query->where(function($q) use ($project) {
                $q->whereNull('idproject')
                ->where('idcompany', $project->idcompany);
            })
            ->orWhere(function($q) use ($project) {
                $q->where('idproject', $project->id);
            });
    }

}
