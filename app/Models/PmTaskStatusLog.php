<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmTaskStatusLog extends Model
{
    protected $table = 'pm_task_status_logs';
    public $timestamps = false;

    protected $fillable = ['legacy_history_id', 'task_id', 'old_stage_id', 'new_stage_id', 'changed_by', 'changed_at', 'note'];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(PmTask::class, 'task_id');
    }

    public function oldStage(): BelongsTo
    {
        return $this->belongsTo(PmStage::class, 'old_stage_id');
    }

    public function newStage(): BelongsTo
    {
        return $this->belongsTo(PmStage::class, 'new_stage_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
