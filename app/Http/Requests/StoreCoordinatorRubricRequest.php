<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoordinatorRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coordinator';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'total_marks' => ['required', 'integer', 'min:1'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.name' => ['required', 'string'],
            'criteria.*.weight' => ['required', 'integer', 'min:1'],
            'criteria.*.description' => ['nullable', 'string'],
            'apply_to_all_supervisors' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please name this grading scheme.',
            'criteria.required' => 'Add at least one criterion.',
            'criteria.*.name.required' => 'Each criterion needs a name.',
            'criteria.*.weight.required' => 'Each criterion needs a weight.',
        ];
    }
}
