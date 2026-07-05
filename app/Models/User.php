<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'login_id',
        'registration_number',
        'staff_id',
        'password',
        'role',
        'department',
        'programme',
        'year_of_study',
        'enrollment_status',
        'account_status',
        'phone_number',
        'gender',
        'must_change_password',
        'notify_email_new_submission',
        'notify_email_submission_reviewed',
        'notify_email_workflow',
        'notify_sms_workflow',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'year_of_study' => 'integer',
            'notify_email_new_submission' => 'boolean',
            'notify_email_submission_reviewed' => 'boolean',
            'notify_email_workflow' => 'boolean',
            'notify_sms_workflow' => 'boolean',
        ];
    }

    public function coordinatedGroups(): HasMany
    {
        return $this->hasMany(ProjectGroup::class, 'coordinator_id');
    }

    public function projectGroups(): BelongsToMany
    {
        return $this->belongsToMany(ProjectGroup::class, 'project_group_members', 'student_id', 'project_group_id')
            ->withTimestamps();
    }

    public function supervisionLogs()
    {
        return $this->hasMany(SupervisionLog::class, $this->role === 'supervisor' ? 'supervisor_id' : 'student_id');
    }

    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(SupervisorAssignment::class, 'supervisor_id');
    }

    public function studentAssignment(): HasOne
    {
        return $this->hasOne(SupervisorAssignment::class, 'student_id');
    }

    public function projectSubmissions(): HasMany
    {
        return $this->hasMany(ProjectSubmission::class, 'student_id');
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withPivot('assigned_by', 'assigned_at', 'is_active')
            ->withTimestamps();
    }

    public function hasRole($roleName): bool
    {
        return $this->roles()->where('role_name', $roleName)->exists() || $this->role === $roleName;
    }

    /**
     * Roles that represent students in self-service profile rules
     * (department, programme, year of study are staff-managed).
     */
    public function isStudentUser(): bool
    {
        return in_array((string) $this->role, [
            'project_student',
            'research_student',
            'normal_student',
            'student',
        ], true);
    }

    public function isAdminUser(): bool
    {
        return (string) $this->role === 'admin';
    }

    /** Student registration number for display and sign-in prompts. */
    public function regNo(): ?string
    {
        if (! $this->isStudentUser()) {
            return null;
        }

        return $this->registration_number ?: $this->login_id;
    }

    /** Account identifier shown in admin lists (reg. no for students, staff email for staff). */
    public function displayIdentifier(): string
    {
        if ($this->isStudentUser()) {
            return $this->regNo() ?? '—';
        }

        return $this->email ?: '—';
    }

    public function displayIdentifierLabel(): string
    {
        return $this->isStudentUser() ? 'Reg. no' : 'Staff email';
    }
}
