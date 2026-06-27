<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StageDeadline extends Model
{
    protected $fillable = [
        'stage_name',
        'academic_year',
        'project_group_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function projectGroup()
    {
        return $this->belongsTo(ProjectGroup::class);
    }
}
