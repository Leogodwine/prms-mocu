<?php

namespace App\Services\Showcase;

use Illuminate\Support\Str;
use ZipArchive;

class DocumentationTextExtractor
{
    public function fromFile(string $absolutePath, ?string $mimeType, ?string $originalName): string
    {
        $extension = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));

        return match ($extension) {
            'txt', 'md' => $this->readPlainText($absolutePath),
            'docx' => $this->readDocx($absolutePath),
            'pdf' => $this->readPdf($absolutePath),
            default => $this->readPlainText($absolutePath),
        };
    }

    public function readmeFromZip(string $absolutePath): string
    {
        if (! class_exists(ZipArchive::class) || ! is_file($absolutePath)) {
            return '';
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return '';
        }

        $readmePath = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $base = strtolower(basename($name));
            if (in_array($base, ['readme.md', 'readme.txt', 'readme'], true)) {
                $readmePath = $name;
                break;
            }
        }

        if ($readmePath === null) {
            $zip->close();

            return '';
        }

        $content = (string) $zip->getFromName($readmePath);
        $zip->close();

        return trim($content);
    }

    private function readPlainText(string $absolutePath): string
    {
        if (! is_readable($absolutePath)) {
            return '';
        }

        $content = file_get_contents($absolutePath);

        return is_string($content) ? trim($content) : '';
    }

    private function readDocx(string $absolutePath): string
    {
        if (! class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return '';
        }

        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === '') {
            return '';
        }

        $text = strip_tags(str_replace(['</w:p>', '<w:tab/>'], ["\n", "\t"], $xml));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function readPdf(string $absolutePath): string
    {
        if (! is_readable($absolutePath)) {
            return '';
        }

        $raw = file_get_contents($absolutePath);
        if (! is_string($raw)) {
            return '';
        }

        $chunks = [];
        if (preg_match_all('/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)\s*Tj/', $raw, $matches)) {
            foreach ($matches[1] as $chunk) {
                $chunks[] = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $chunk);
            }
        }

        return trim(implode(' ', $chunks));
    }

    public function truncate(string $text, int $limit = 12000): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return Str::limit($text, $limit, '…');
    }
}
