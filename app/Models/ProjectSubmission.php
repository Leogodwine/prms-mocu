<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectSubmission extends Model
{
    protected $fillable = [
        'project_group_id',
        'student_id',
        'stage',
        'title',
        'description',
        'demo_url',
        'video_url',
        'version',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'onlyoffice_key',
        'screenshot_path',
        'screenshot_original_filename',
        'screenshot_mime_type',
        'documentation_path',
        'documentation_original_filename',
        'documentation_mime_type',
        'showcase_doc_summary',
        'showcase_doc_significance',
        'showcase_readme_body',
        'showcase_archive_tree',
        'showcase_analysis_status',
        'status',
        'submitted_to_coordinator',
        'coordinator_approved_at',
        'coordinator_approved_by',
        'coordinator_signature_path',
        'coordinator_consent_pdf_path',
        'repository_published_at',
        'supervisor_consent_signed_by',
        'supervisor_consent_signed_at',
        'supervisor_signature_path',
        'supervisor_consent_pdf_path',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'coordinator_approved_at' => 'datetime',
            'repository_published_at' => 'datetime',
            'supervisor_consent_signed_at' => 'datetime',
            'showcase_archive_tree' => 'array',
        ];
    }

    public function projectGroup(): BelongsTo
    {
        return $this->belongsTo(ProjectGroup::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(SubmissionFeedback::class);
    }

    public function interfaceScreenshots(): HasMany
    {
        return $this->hasMany(ProjectSubmissionScreenshot::class, 'project_submission_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function primaryInterfaceScreenshot(): ?ProjectSubmissionScreenshot
    {
        if ($this->relationLoaded('interfaceScreenshots')) {
            return $this->interfaceScreenshots->first();
        }

        return $this->interfaceScreenshots()->first();
    }

    /**
     * Convenience flag — true when this submission represents a project
     * complete-system showcase (rich upload form with interfaces & source).
     */
    public function isProjectShowcase(): bool
    {
        return \App\Support\StudentStageProgress::isCompleteSystemStage((string) $this->stage)
            || str_contains(strtolower((string) $this->stage), 'source')
            || (bool) $this->demo_url
            || (bool) $this->video_url
            || (bool) $this->screenshot_path
            || ($this->relationLoaded('interfaceScreenshots')
                ? $this->interfaceScreenshots->isNotEmpty()
                : $this->interfaceScreenshots()->exists());
    }

    public function getWorkTypeAttribute(): string
    {
        return \App\Support\StudentStageProgress::workTypeFromStage((string) $this->stage);
    }

    /**
     * Convert YouTube / Vimeo / Loom watch URLs into iframe-embed URLs so
     * the showcase modal can render them inline. Falls back to the raw
     * URL when the platform is unknown — the front-end then renders a
     * "Open video" link rather than an iframe.
     *
     * Supported patterns:
     *   • https://youtu.be/<id>
     *   • https://www.youtube.com/watch?v=<id>
     *   • https://www.youtube.com/shorts/<id>
     *   • https://vimeo.com/<id>
     *   • https://www.loom.com/share/<id>
     */
    public function getEmbeddableVideoUrlAttribute(): ?string
    {
        $raw = trim((string) $this->video_url);
        if ($raw === '') {
            return null;
        }

        // YouTube — youtu.be / watch?v= / shorts/
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|shorts/|embed/))([A-Za-z0-9_-]{6,})~i', $raw, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // Vimeo
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $raw, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        // Loom
        if (preg_match('~loom\.com/share/([A-Za-z0-9-]+)~i', $raw, $m)) {
            return 'https://www.loom.com/embed/' . $m[1];
        }

        return null; // unknown platform — caller renders an "open" link
    }

    public function isWordDocument(): bool
    {
        $extension = strtolower(pathinfo((string) $this->original_filename, PATHINFO_EXTENSION));

        if (in_array($extension, ['doc', 'docx'], true)) {
            return true;
        }

        $mime = strtolower((string) $this->mime_type);

        return str_contains($mime, 'wordprocessingml') || str_contains($mime, 'msword');
    }

    public function refreshOnlyOfficeKey(): string
    {
        $key = md5('submission_'.$this->id.'_'.$this->file_path.'_'.$this->updated_at?->timestamp.'_'.microtime(true));

        $this->update(['onlyoffice_key' => $key]);

        return $key;
    }
}
