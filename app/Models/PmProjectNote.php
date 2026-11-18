<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmProjectNote extends Model
{
    protected $table = 'pm_project_notes';

    protected $fillable = [
        'legacy_note_id',
        'project_id',
        'created_by',
        'title',
        'content',
        'note_date',
    ];

    protected function casts(): array
    {
        return ['note_date' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PmProject::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
