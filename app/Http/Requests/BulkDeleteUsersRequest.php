<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $currentUserId = (int) ($this->user()?->id ?? 0);

        $ids = collect($this->input('user_ids', []))
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0 && $id !== $currentUserId)
            ->unique()
            ->values()
            ->all();

        $this->merge(['user_ids' => $ids]);
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Select at least one user to delete.',
            'user_ids.min' => 'Select at least one user to delete.',
            'user_ids.*.exists' => 'One or more selected users could not be found.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.users.index');
    }
}
