<?php

namespace App\Models;

use App\Support\StudentGenderNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'registration_number',
        'full_name',
        'gender',
        'programme_id',
        'department_id',
        'academic_level',
        'workflow_role',
        'output_track',
        'year_of_study',
        'enrollment_status',
        'university_email',
        'personal_email',
        'phone_number',
        'admission_date',
        'expected_graduation',
        'sis_data',
        'sis_sync_date',
    ];

    protected $casts = [
        'sis_data' => 'array',
        'admission_date' => 'date',
        'expected_graduation' => 'date',
        'sis_sync_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function programme()
    {
        return $this->belongsTo(Program::class, 'programme_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Normalized gender for grouping (male, female, or null when unknown).
     */
    public function normalizedGender(): ?string
    {
        $fromColumn = StudentGenderNormalizer::normalize($this->gender);
        if ($fromColumn !== null) {
            return $fromColumn;
        }

        return StudentGenderNormalizer::normalize(
            data_get($this->sis_data, 'gender') ?? data_get($this->sis_data, 'sex')
        );
    }

    public function genderLabel(): string
    {
        return match ($this->normalizedGender()) {
            'male' => 'Male',
            'female' => 'Female',
            default => '—',
        };
    }
}
