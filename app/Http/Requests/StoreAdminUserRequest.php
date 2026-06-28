<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    public const FORM_STUDENT_ROLE = 'student';

    /** @var list<string> */
    public const STUDENT_ROLES = ['project_student', 'research_student', 'normal_student', 'student'];

    /** @var list<string> */
    public const STAFF_FORM_ROLES = ['admin', 'hod', 'coordinator', 'supervisor'];

    /** @var list<string> */
    public const FORM_ROLES = ['admin', 'hod', 'coordinator', 'supervisor', self::FORM_STUDENT_ROLE];

    /** @var list<string> Roles allowed in CSV bulk import (students and staff only — not admin). */
    public const BULK_IMPORT_ROLES = ['hod', 'coordinator', 'supervisor', self::FORM_STUDENT_ROLE];

    public static function isStudentFormRole(string $role): bool
    {
        return $role === self::FORM_STUDENT_ROLE || in_array($role, self::STUDENT_ROLES, true);
    }

    public static function isStaffFormRole(string $role): bool
    {
        return in_array($role, self::STAFF_FORM_ROLES, true);
    }

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'form_context' => $this->input('form_context', 'create'),
        ];

        if ($this->input('year_of_study') === '') {
            $merge['year_of_study'] = null;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $role = (string) $this->input('role');
        $isStudent = self::isStudentFormRole($role);
        $isStaff = self::isStaffFormRole($role);

        return [
            'form_context' => ['required', 'in:create'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'login_id' => ['required', 'string', 'max:80', 'unique:users,login_id'],
            'role' => ['required', Rule::in(self::FORM_ROLES)],
            'department' => [
                Rule::requiredIf($isStudent || in_array($role, ['hod', 'coordinator', 'supervisor'], true)),
                'nullable',
                'string',
                'max:120',
            ],
            'programme' => [
                Rule::requiredIf($isStudent),
                'nullable',
                'string',
                'max:120',
            ],
            'year_of_study' => [
                Rule::requiredIf($isStudent),
                'nullable',
                'integer',
                'between:1,8',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the full name.',
            'email.required' => 'Email is required.',
            'email.unique' => 'This email is already in use.',
            'login_id.required' => 'Registration number or staff ID is required.',
            'login_id.unique' => 'This registration number or staff ID is already taken.',
            'role.required' => 'Please select a system role.',
            'department.required' => 'Department is required for this role.',
            'programme.required' => 'Programme is required for student accounts.',
            'year_of_study.required' => 'Year of study is required for student accounts.',
        ];
    }
}
