<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'coordinator_id',
        'academic_year',
    ];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_group_members', 'project_group_id', 'student_id')
            ->withTimestamps();
    }

    public function supervisionLogs(): HasMany
    {
        return $this->hasMany(SupervisionLog::class);
    }

    public function supervisorAssignment(): HasOne
    {
        return $this->hasOne(SupervisorAssignment::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ProjectSubmission::class);
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(StageDeadline::class);
    }
}
