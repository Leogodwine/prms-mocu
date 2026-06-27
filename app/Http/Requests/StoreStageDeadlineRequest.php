<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStageDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coordinator';
    }

    public function rules(): array
    {
        return [
            'stage_name' => ['required', 'string'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'project_group_id' => ['nullable', 'exists:project_groups,id'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'stage_name.required' => 'Select the stage this deadline applies to.',
            'end_time.required' => 'An end date and time is required.',
            'end_time.after' => 'The end time must be after the start time.',
        ];
    }
}
