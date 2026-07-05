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

    public function test_deployment_warnings_flag_localhost_storage_when_browsing_remotely(): void
    {
        config([
            'onlyoffice.storage_url' => 'http://127.0.0.1:8000',
            'onlyoffice.document_server_url' => 'http://127.0.0.1:8080',
            'app.url' => 'http://127.0.0.1:8000',
        ]);

        $request = \Illuminate\Http\Request::create('http://192.168.1.50/student', 'GET');
        $this->app->instance('request', $request);

        $warnings = app(OnlyOfficeService::class)->deploymentWarnings();

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('ONLYOFFICE_STORAGE_URL', implode(' ', $warnings));
    }

    public function test_deployment_warnings_ignore_docker_internal_storage_for_local_app(): void
    {
        config([
            'onlyoffice.storage_url' => 'http://host.docker.internal:8000',
            'onlyoffice.document_server_url' => 'http://127.0.0.1:8080',
            'app.url' => 'http://127.0.0.1:8000',
            'onlyoffice.jwt_enabled' => false,
            'onlyoffice.jwt_secret' => '',
        ]);

        $request = \Illuminate\Http\Request::create('http://127.0.0.1:8000/student', 'GET');
        $this->app->instance('request', $request);

        $warnings = app(OnlyOfficeService::class)->deploymentWarnings();

        $this->assertSame([], $warnings);
    }
}
