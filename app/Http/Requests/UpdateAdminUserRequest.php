<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $rules = [
            'edit_user_id' => ['nullable', 'integer'],
            'role' => ['required', Rule::in(['admin', 'hod', 'coordinator', 'supervisor', 'project_student', 'research_student', 'normal_student'])],
            'account_status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'locked'])],
        ];

        /** @var User|null $target */
        $target = $this->route('user');
        if ($target && $target->isStudentUser()) {
            if (Schema::hasColumn('users', 'department')) {
                $rules['department'] = ['nullable', 'string', 'max:120'];
            }
            if (Schema::hasColumn('users', 'programme')) {
                $rules['programme'] = ['nullable', 'string', 'max:120'];
            }
            if (Schema::hasColumn('users', 'year_of_study')) {
                $rules['year_of_study'] = ['nullable', 'integer', 'between:1,8'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Role selection is required.',
            'account_status.required' => 'Status selection is required.',
        ];
    }
}
