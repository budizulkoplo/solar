<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmStage extends Model
{
    protected $table = 'pm_stages';

    protected $fillable = ['legacy_status_id', 'project_id', 'name', 'slug', 'sort_order', 'is_done', 'color'];

    protected function casts(): array
    {
        return ['is_done' => 'boolean'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PmProject::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PmTask::class, 'stage_id');
    }
}
