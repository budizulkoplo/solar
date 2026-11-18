<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmEpic extends Model
{
    protected $table = 'pm_epics';

    protected $fillable = [
        'legacy_epic_id',
        'project_id',
        'name',
        'description',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PmProject::class, 'project_id');
    }
}
