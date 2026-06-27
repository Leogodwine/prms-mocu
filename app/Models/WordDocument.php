<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WordDocument extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'onlyoffice_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refreshOnlyOfficeKey(): string
    {
        $key = md5($this->id.'_'.$this->file_path.'_'.$this->updated_at?->timestamp.'_'.microtime(true));

        $this->update(['onlyoffice_key' => $key]);

        return $key;
    }

    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
    }
}
