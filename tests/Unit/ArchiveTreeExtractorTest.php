<?php

namespace Tests\Unit;

use App\Services\Showcase\ArchiveTreeExtractor;
use Tests\TestCase;
use ZipArchive;

class ArchiveTreeExtractorTest extends TestCase
{
    public function test_extracts_top_level_entries_from_zip(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is not available.');
        }

        $zipPath = storage_path('framework/testing/showcase-tree.zip');
        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0777, true);
        }

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('distributed-system/app/Kernel.php', '<?php');
        $zip->addFromString('distributed-system/docs/manual.pdf', '%PDF');
        $zip->addFromString('distributed-system/README.md', '# README');
        $zip->close();

        $tree = (new ArchiveTreeExtractor())->extractFromZip($zipPath);

        $names = array_column($tree, 'name');
        $this->assertContains('app', $names);
        $this->assertContains('docs', $names);
        $this->assertContains('README.md', $names);

        @unlink($zipPath);
    }
}
