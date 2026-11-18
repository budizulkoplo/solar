<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmProject extends Model
{
    protected $table = 'pm_projects';

    protected $fillable = [
        'legacy_project_id',
        'name',
        'slug',
        'description',
        'ticket_prefix',
        'start_date',
        'end_date',
        'pinned_date',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'pinned_date' => 'datetime',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PmStage::class, 'project_id')->orderBy('sort_order')->orderBy('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PmTask::class, 'project_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pm_project_members', 'project_id', 'user_id')->withTimestamps();
    }

    public function epics(): HasMany
    {
        return $this->hasMany(PmEpic::class, 'project_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PmProjectNote::class, 'project_id')->latest('note_date')->latest('id');
    }
}
