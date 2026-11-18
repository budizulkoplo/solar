<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmTaskComment extends Model
{
    protected $table = 'pm_task_comments';

    protected $fillable = ['legacy_comment_id', 'task_id', 'user_id', 'message'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(PmTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
