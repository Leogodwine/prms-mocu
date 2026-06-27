<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'project_id',
        'document_type',
        'file_name',
        'file_path',
        'preview_file_path',
        'searchable_text',
        'file_size',
        'mime_type',
        'file_hash',
        'version_number',
        'annotation_enabled',
        'collaboration_enabled',
        'is_current_version',
        'uploaded_by',
        'upload_date',
        'description',
        'metadata_json',
        'ai_summary',
        'ai_keywords',
        'is_public',
        'embargo_until',
        'download_count',
    ];

    protected $casts = [
        'annotation_enabled' => 'boolean',
        'collaboration_enabled' => 'boolean',
        'is_current_version' => 'boolean',
        'is_public' => 'boolean',
        'metadata_json' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

