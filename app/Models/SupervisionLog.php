<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupervisionLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_group_id',
        'student_id',
        'supervisor_id',
        'meeting_starts_at',
        'meeting_ends_at',
        'summary',
        'progress_score',
        'next_steps',
    ];

    protected function casts(): array
    {
        return [
            'meeting_starts_at' => 'datetime',
            'meeting_ends_at' => 'datetime',
        ];
    }

    public function projectGroup()
    {
        return $this->belongsTo(ProjectGroup::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
