<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkNotificationIdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $ids = collect($this->input('notification_ids', []))
            ->map(static fn ($id) => trim((string) $id))
            ->filter(static fn (string $id) => $id !== '')
            ->unique()
            ->values()
            ->all();

        $this->merge(['notification_ids' => $ids]);
    }

    public function rules(): array
    {
        return [
            'notification_ids' => ['required', 'array', 'min:1'],
            'notification_ids.*' => ['uuid', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'notification_ids.required' => 'Select at least one notification.',
            'notification_ids.min' => 'Select at least one notification.',
            'notification_ids.*.uuid' => 'One or more selected notifications are invalid.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('notifications.index');
    }
}
