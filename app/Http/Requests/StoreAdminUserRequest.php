<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    /** @var list<string> */
    public const STUDENT_ROLES = ['project_student', 'research_student', 'normal_student'];

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

        return [
            'form_context' => ['required', 'in:create'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'login_id' => ['required', 'string', 'max:80', 'unique:users,login_id'],
            'role' => ['required', Rule::in(array_merge(['admin', 'hod', 'coordinator', 'supervisor'], self::STUDENT_ROLES))],
            'department' => ['required', 'string', 'max:120'],
            'programme' => ['required', 'string', 'max:120'],
            'year_of_study' => [
                Rule::requiredIf(in_array($role, self::STUDENT_ROLES, true)),
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
            'login_id.required' => 'Reg. no / Staff ID is required.',
            'login_id.unique' => 'This reg. no or staff ID is already taken.',
            'role.required' => 'Please select a system role.',
            'department.required' => 'Department is required.',
            'programme.required' => 'Programme is required.',
            'year_of_study.required' => 'Year of study is required for student accounts.',
        ];
    }
}
