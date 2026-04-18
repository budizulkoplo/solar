<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitDetailUpdateLog extends Model
{
    use SoftDeletes;

    protected $table = 'unit_details_update_log';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'idunitdetail',
        'old_status',
        'new_status',
        'updatetime',
        'update_user',
    ];

    protected $casts = [
        'updatetime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function unitDetail()
    {
        return $this->belongsTo(UnitDetail::class, 'idunitdetail');
    }
}
