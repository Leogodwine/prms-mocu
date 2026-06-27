<?php

namespace App\Services\Showcase;

use Illuminate\Support\Str;
use ZipArchive;

class ArchiveTreeExtractor
{
    /**
     * @return list<array{name: string, type: 'dir'|'file'}>
     */
    public function extractFromZip(string $absolutePath): array
    {
        if (! is_file($absolutePath) || ! class_exists(ZipArchive::class)) {
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return [];
        }

        $paths = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if ($name === '' || str_starts_with($name, '__MACOSX/') || str_contains($name, '/.')) {
                continue;
            }
            $paths[] = str_replace('\\', '/', $name);
        }
        $zip->close();

        if ($paths === []) {
            return [];
        }

        $prefix = $this->commonRootPrefix($paths);
        $entries = [];

        foreach ($paths as $path) {
            $relative = ltrim(Str::after($path, $prefix), '/');
            if ($relative === '' || str_ends_with($relative, '/')) {
                continue;
            }

            $segment = explode('/', $relative)[0];
            if ($segment === '' || isset($entries[$segment])) {
                continue;
            }

            $isDir = str_contains($relative, '/');
            $entries[$segment] = [
                'name' => $segment,
                'type' => $isDir ? 'dir' : 'file',
            ];
        }

        $sorted = array_values($entries);
        usort($sorted, function (array $a, array $b) {
            if ($a['type'] === $b['type']) {
                return strnatcasecmp($a['name'], $b['name']);
            }

            return $a['type'] === 'dir' ? -1 : 1;
        });

        return array_slice($sorted, 0, 40);
    }

    /**
     * @param  list<string>  $paths
     */
    private function commonRootPrefix(array $paths): string
    {
        $firstSegments = [];
        foreach ($paths as $path) {
            $parts = explode('/', trim($path, '/'));
            if ($parts[0] !== '') {
                $firstSegments[$parts[0]] = ($firstSegments[$parts[0]] ?? 0) + 1;
            }
        }

        if ($firstSegments === []) {
            return '';
        }

        arsort($firstSegments);
        $top = (string) array_key_first($firstSegments);
        $count = reset($firstSegments);

        if ($count >= (int) floor(count($paths) * 0.6)) {
            return $top.'/';
        }

        return '';
    }
}
