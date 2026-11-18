<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmTask extends Model
{
    protected $table = 'pm_tasks';

    protected $fillable = [
        'legacy_ticket_id',
        'uuid',
        'project_id',
        'stage_id',
        'legacy_priority_id',
        'legacy_epic_id',
        'epic',
        'title',
        'priority',
        'description',
        'start_date',
        'due_date',
        'created_by',
        'hidden_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'hidden_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PmProject::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PmStage::class, 'stage_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pm_task_assignees', 'task_id', 'user_id')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PmTaskComment::class, 'task_id')->oldest();
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PmTaskStatusLog::class, 'task_id')->latest('changed_at')->latest('id');
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(PmEpic::class, 'legacy_epic_id', 'legacy_epic_id');
    }
}
