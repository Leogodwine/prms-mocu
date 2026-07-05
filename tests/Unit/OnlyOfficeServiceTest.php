<?php

namespace Tests\Unit;

use App\Services\OnlyOfficeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnlyOfficeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_blank_docx_uses_bundled_template_without_zip_extension(): void
    {
        Storage::fake('public');

        $this->assertFileExists(resource_path('onlyoffice/blank.docx'));

        $meta = app(OnlyOfficeService::class)->createBlankDocx('Untitled Document');

        $this->assertSame('Untitled Document.docx', $meta['original_filename']);
        $this->assertTrue(Storage::disk('public')->exists($meta['file_path']));
        $this->assertGreaterThan(500, Storage::disk('public')->size($meta['file_path']));
    }
}
