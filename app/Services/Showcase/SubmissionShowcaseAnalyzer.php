<?php

namespace App\Services\Showcase;

use App\Models\ProjectSubmission;
use App\Services\Ollama\OllamaClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SubmissionShowcaseAnalyzer
{
    public function __construct(
        private readonly ArchiveTreeExtractor $archiveTree,
        private readonly DocumentationTextExtractor $textExtractor,
        private readonly OllamaClient $ollama,
    ) {}

    public function analyze(ProjectSubmission $submission): void
    {
        if (! $submission->isProjectShowcase()) {
            $submission->update(['showcase_analysis_status' => 'skipped']);

            return;
        }

        $submission->update(['showcase_analysis_status' => 'pending']);

        $tree = $this->extractArchiveTree($submission);
        $sourceText = $this->collectSourceText($submission);

        $overview = null;
        $significance = null;
        $readmeBody = null;

        if (strlen($sourceText) >= 80 && $this->ollama->isEnabled()) {
            try {
                $summary = $this->ollama->summarizeShowcaseDocumentation(
                    (string) ($submission->title ?: 'Project'),
                    $this->textExtractor->truncate($sourceText)
                );

                if ($summary) {
                    $overview = $summary['overview'] ?: null;
                    $significance = $summary['significance'] ?: null;
                    $readmeBody = $summary['readme_body'] ?: null;
                }
            } catch (\Throwable $e) {
                Log::warning('Showcase Ollama summary failed', [
                    'submission_id' => $submission->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($overview === null || $overview === '') {
            $overview = $this->fallbackOverview();
        }

        if ($significance === null || $significance === '') {
            $significance = trim((string) ($submission->description ?? '')) ?: $this->fallbackSignificance();
        }

        if ($readmeBody === null || $readmeBody === '') {
            $readmeBody = $this->fallbackReadme($submission, $sourceText);
        }

        $submission->update([
            'showcase_archive_tree' => $tree !== [] ? $tree : null,
            'showcase_doc_summary' => $overview,
            'showcase_doc_significance' => $significance,
            'showcase_readme_body' => $readmeBody,
            'showcase_analysis_status' => 'completed',
        ]);
    }

    public function markFailed(ProjectSubmission $submission): void
    {
        $submission->update(['showcase_analysis_status' => 'failed']);
    }

    /**
     * @return list<array{name: string, type: string}>
     */
    private function extractArchiveTree(ProjectSubmission $submission): array
    {
        if (! $submission->file_path) {
            return [];
        }

        $diskPath = Storage::disk('public')->path($submission->file_path);
        $extension = strtolower(pathinfo((string) $submission->original_filename, PATHINFO_EXTENSION));

        if ($extension !== 'zip') {
            return [];
        }

        return $this->archiveTree->extractFromZip($diskPath);
    }

    private function collectSourceText(ProjectSubmission $submission): string
    {
        $parts = [];

        if ($submission->documentation_path) {
            $docPath = Storage::disk('public')->path($submission->documentation_path);
            $docText = $this->textExtractor->fromFile(
                $docPath,
                $submission->documentation_mime_type,
                $submission->documentation_original_filename
            );
            if ($docText !== '') {
                $parts[] = $docText;
            }
        }

        if ($submission->file_path) {
            $archivePath = Storage::disk('public')->path($submission->file_path);
            $readme = $this->textExtractor->readmeFromZip($archivePath);
            if ($readme !== '') {
                $parts[] = $readme;
            }
        }

        if (trim((string) $submission->description) !== '') {
            $parts[] = trim((string) $submission->description);
        }

        return trim(implode("\n\n", array_filter($parts)));
    }

    private function fallbackOverview(): string
    {
        return 'It includes a structured layout system (sidebar navigation, topbar, content area), plus reusable components for everyday work: tables, forms, charts, cards, modals, notifications, and more—so you can assemble pages quickly without starting from scratch.';
    }

    private function fallbackSignificance(): string
    {
        return 'This repository documents the design, implementation, and operational significance of the submitted system—including architecture decisions, user roles, and how the solution addresses real-world requirements.';
    }

    private function fallbackReadme(ProjectSubmission $submission, string $sourceText): string
    {
        $title = $submission->title ?: 'Project README';
        $intro = $this->textExtractor->truncate($sourceText, 600);
        $version = $submission->version ?? 1;

        return implode("\n", array_filter([
            '# '.$title,
            '',
            $intro !== '' ? $intro : 'Submitted software project for academic review.',
            '',
            '## Version',
            'Submitted version: v'.$version,
        ]));
    }
}
