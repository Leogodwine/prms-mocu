<?php

namespace App\Http\Requests;

use App\Support\AdminUserImportReader;
use Illuminate\Foundation\Http\FormRequest;

class BulkImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'import_file' => [
                'required',
                'file',
                'mimes:csv,txt,xml,pdf',
                'max:'.AdminUserImportReader::MAX_KILOBYTES,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'import_file.required' => 'Please choose a file to import.',
            'import_file.mimes' => 'The import file must be CSV, XML, or PDF.',
            'import_file.max' => 'The import file may not be larger than 10 MB.',
        ];
    }
}
