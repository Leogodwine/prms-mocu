<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'login_id' => ['required', 'string', 'max:80', 'unique:users,login_id'],
            'role' => ['required', Rule::in(['admin', 'hod', 'coordinator', 'supervisor', 'project_student', 'research_student', 'normal_student'])],
            'department' => ['nullable', 'string', 'max:120'],
            'programme' => ['nullable', 'string', 'max:120'],
            'year_of_study' => ['nullable', 'integer', 'between:1,8'],
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
        ];
    }
}
