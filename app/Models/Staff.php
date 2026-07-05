<?php

namespace App\Models;

use App\Support\StudentGenderNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_number',
        'full_name',
        'gender',
        'designation',
        'department_id',
        'email',
        'phone_number',
        'office_location',
        'max_students_allowed',
        'current_student_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_students_allowed' => 'integer',
        'current_student_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Supervision assignments where this staff member is the supervisor (via users.id).
     */
    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(SupervisorAssignment::class, 'supervisor_id', 'user_id');
    }

    public function genderLabel(): string
    {
        return match (StudentGenderNormalizer::normalize($this->gender)) {
            'male' => 'Male',
            'female' => 'Female',
            default => '—',
        };
    }
}
