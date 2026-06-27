<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-02 — Project, research report and proposal management.
 *
 * Stores every uploaded artefact (proposal, dissertation, thesis,
 * project report, supplementary file) attached to a research
 * project, alongside the metadata fields the App\Models\Document
 * fillable list expects.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documents')) {
            return;
        }

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('research_projects')->cascadeOnDelete();

            $table->string('document_type', 60);
            $table->string('file_name');
            $table->string('file_path', 1000);
            $table->string('preview_file_path', 1000)->nullable();
            $table->longText('searchable_text')->nullable();

            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type', 200)->nullable();
            $table->string('file_hash', 128)->nullable();

            $table->unsignedInteger('version_number')->default(1);
            $table->boolean('annotation_enabled')->default(true);
            $table->boolean('collaboration_enabled')->default(true);
            $table->boolean('is_current_version')->default(true);

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('upload_date')->nullable();

            $table->text('description')->nullable();
            $table->json('metadata_json')->nullable();
            $table->longText('ai_summary')->nullable();
            $table->text('ai_keywords')->nullable();

            $table->boolean('is_public')->default(false);
            $table->date('embargo_until')->nullable();
            $table->unsignedInteger('download_count')->default(0);

            $table->timestamps();

            $table->index(['project_id', 'document_type'], 'documents_project_type_idx');
            $table->index('is_current_version', 'documents_current_idx');
            $table->index('file_hash', 'documents_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
