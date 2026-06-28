<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    /** @var list<string> */
    public const STUDENT_ROLES = StoreAdminUserRequest::STUDENT_ROLES;

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'form_context' => $this->input('form_context', 'edit'),
        ];

        if ($this->input('year_of_study') === '') {
            $merge['year_of_study'] = null;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');
        $userId = $user?->id ?? (int) $this->input('edit_user_id');
        $role = (string) $this->input('role');
        $isStudent = StoreAdminUserRequest::isStudentFormRole($role);

        $rules = [
            'form_context' => ['required', 'in:edit'],
            'edit_user_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'login_id' => ['required', 'string', 'max:80', Rule::unique('users', 'login_id')->ignore($userId)],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(StoreAdminUserRequest::FORM_ROLES)],
            'account_status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'locked'])],
        ];

        if (Schema::hasColumn('users', 'registration_number') && $isStudent) {
            $rules['login_id'][] = Rule::unique('users', 'registration_number')->ignore($userId);
        }

        if (Schema::hasColumn('users', 'staff_id') && StoreAdminUserRequest::isStaffFormRole($role)) {
            $rules['login_id'][] = Rule::unique('users', 'staff_id')->ignore($userId);
        }

        if (Schema::hasColumn('users', 'department')) {
            $rules['department'] = [
                Rule::requiredIf($isStudent || in_array($role, ['hod', 'coordinator', 'supervisor'], true)),
                'nullable',
                'string',
                'max:120',
            ];
        }

        if (Schema::hasColumn('users', 'programme')) {
            $rules['programme'] = [
                Rule::requiredIf($isStudent),
                'nullable',
                'string',
                'max:120',
            ];
        }

        if (Schema::hasColumn('users', 'year_of_study')) {
            $rules['year_of_study'] = [
                Rule::requiredIf($isStudent),
                'nullable',
                'integer',
                'between:1,8',
            ];
        }

        return $rules;
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
            'account_status.required' => 'Status selection is required.',
            'department.required' => 'Department is required for this role.',
            'programme.required' => 'Programme is required for student accounts.',
            'year_of_study.required' => 'Year of study is required for student accounts.',
        ];
    }
}
